<?php

namespace App\Services;

use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\WalletPass;
use App\Models\WalletPassRegistration;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletPassSyncService
{
    public function __construct(
        private readonly ApplePassPushService $applePassPushService
    ) {}

    public function ensurePassForMember(Member $member, string $serialNumber, ?string $passTypeIdentifier = null): WalletPass
    {
        $passType = $passTypeIdentifier !== null && $passTypeIdentifier !== ''
            ? $passTypeIdentifier
            : (string) config('wallet_pass.type_id', '');

        $projectId = (int) $member->project_id;
        $userId = (int) $member->user_id;

        return DB::transaction(function () use ($projectId, $userId, $member, $passType, $serialNumber): WalletPass {
            $existing = WalletPass::where('project_id', $projectId)
                ->where('pass_type_identifier', $passType)
                ->where('serial_number', $serialNumber)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                $needsSave = false;
                if ((int) $existing->user_id !== $userId) {
                    $existing->user_id = $userId;
                    $needsSave = true;
                }
                if ((int) ($existing->member_id ?? 0) !== (int) $member->id) {
                    $existing->member_id = (int) $member->id;
                    $needsSave = true;
                }
                if ($needsSave) {
                    $existing->save();
                }

                return $existing;
            }

            $length = max(16, (int) config('wallet_pass.auth_token_length', 32));

            return WalletPass::create([
                'project_id' => $projectId,
                'user_id' => $userId,
                'member_id' => (int) $member->id,
                'pass_type_identifier' => $passType,
                'serial_number' => $serialNumber,
                'authentication_token' => Str::random($length),
                'last_updated_at' => now(),
            ]);
        });
    }

    public function findPassByTypeAndSerial(int $projectId, string $passTypeIdentifier, string $serialNumber): ?WalletPass
    {
        return WalletPass::where('project_id', $projectId)
            ->where('pass_type_identifier', $passTypeIdentifier)
            ->where('serial_number', $serialNumber)
            ->first();
    }

    public function registerDevice(WalletPass $pass, string $deviceLibraryIdentifier, string $pushToken): bool
    {
        $registration = WalletPassRegistration::firstOrNew([
            'wallet_pass_id' => $pass->id,
            'device_library_identifier' => $deviceLibraryIdentifier,
        ]);
        $created = ! $registration->exists;
        $registration->fill([
            'project_id' => (int) $pass->project_id,
            'push_token' => $pushToken,
        ]);
        $registration->save();

        return $created;
    }

    public function unregisterDevice(WalletPass $pass, string $deviceLibraryIdentifier): bool
    {
        $deleted = WalletPassRegistration::where('wallet_pass_id', $pass->id)
            ->where('device_library_identifier', $deviceLibraryIdentifier)
            ->delete();

        return $deleted > 0;
    }

    /**
     * @return array{serialNumbers: array<int, string>, lastUpdated: string}|null
     */
    public function listUpdatedSerials(int $projectId, string $deviceLibraryIdentifier, string $passTypeIdentifier, ?string $passesUpdatedSince): ?array
    {
        $query = WalletPassRegistration::query()
            ->select(['wallet_passes.serial_number', 'wallet_passes.last_updated_at', 'wallet_passes.updated_at'])
            ->join('wallet_passes', 'wallet_passes.id', '=', 'wallet_pass_registrations.wallet_pass_id')
            ->where('wallet_pass_registrations.project_id', $projectId)
            ->where('wallet_passes.project_id', $projectId)
            ->where('wallet_pass_registrations.device_library_identifier', $deviceLibraryIdentifier)
            ->where('wallet_passes.pass_type_identifier', $passTypeIdentifier);

        $since = $this->parseSince($passesUpdatedSince);
        if ($since !== null) {
            $query->where(function ($q) use ($since): void {
                $q->where('wallet_passes.last_updated_at', '>', $since)
                    ->orWhere(function ($q2) use ($since): void {
                        $q2->whereNull('wallet_passes.last_updated_at')
                            ->where('wallet_passes.updated_at', '>', $since);
                    });
            });
        }

        $rows = $query->orderBy('wallet_passes.updated_at')->get();
        if ($rows->isEmpty()) {
            return null;
        }

        $serials = [];
        $latest = null;
        foreach ($rows as $row) {
            $serials[] = (string) $row->serial_number;
            $updatedRaw = $row->last_updated_at ?? $row->updated_at;
            if ($updatedRaw === null) {
                continue;
            }
            $updated = CarbonImmutable::parse((string) $updatedRaw);
            if ($latest === null || $updated->gt($latest)) {
                $latest = $updated;
            }
        }

        return [
            'serialNumbers' => array_values(array_unique($serials)),
            'lastUpdated' => ($latest ?? now())->toIso8601String(),
        ];
    }

    public function touchAndNotifyByPointTransaction(PointTransaction $transaction): void
    {
        $memberId = (int) ($transaction->member_id ?? 0);
        if ($memberId <= 0) {
            return;
        }

        $passes = WalletPass::where('project_id', (int) $transaction->project_id)
            ->where('member_id', $memberId)
            ->get();

        if ($passes->isEmpty()) {
            return;
        }

        $passIds = $passes->pluck('id')->all();
        WalletPass::whereIn('id', $passIds)->update([
            'last_updated_at' => now(),
            'updated_at' => now(),
        ]);

        $pushTokens = WalletPassRegistration::whereIn('wallet_pass_id', $passIds)
            ->pluck('push_token')
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->map(fn ($v) => trim((string) $v))
            ->values()
            ->all();

        $this->applePassPushService->pushWalletPassUpdated($pushTokens);
    }

    public function resolveWebServiceUrlForProject(Project $project): string
    {
        $base = rtrim((string) config('wallet_pass.web_service_url', ''), '/');
        if ($base === '') {
            $base = rtrim((string) config('app.url', 'http://localhost'), '/').'/api/wallet/v1/{project_key}';
        }

        if (str_contains($base, '{project_key}')) {
            return str_replace('{project_key}', (string) $project->project_key, $base);
        }

        return $base.'/'.(string) $project->project_key;
    }

    private function parseSince(?string $raw): ?CarbonImmutable
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
