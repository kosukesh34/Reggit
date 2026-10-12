<?php

namespace App\Services;

use App\Models\Member;
use App\Models\WalletPassRegistration;
use App\Models\WalletPassUpdate;

class AppleWalletPasskitService
{
    public function __construct(
        private WalletPassAuthTokenService $authTokenService,
        private WalletPassService $walletPassService,
        private WalletPassSyncService $walletPassSyncService,
    ) {}

    public function parseAuthToken(string $authorizationHeader): ?string
    {
        if (! str_starts_with($authorizationHeader, 'ApplePass ')) {
            return null;
        }

        $token = trim(substr($authorizationHeader, strlen('ApplePass ')));

        return $token === '' ? null : $token;
    }

    public function resolveMember(string $passTypeIdentifier, string $serialNumber): ?Member
    {
        if ($passTypeIdentifier !== (string) config('wallet_pass.type_id', '')) {
            return null;
        }
        if (! str_starts_with($serialNumber, 'member-')) {
            return null;
        }

        $memberId = (int) substr($serialNumber, strlen('member-'));
        if ($memberId <= 0) {
            return null;
        }

        return Member::query()->with(['user', 'project'])->find($memberId);
    }

    public function isAuthorizedForMember(Member $member, string $authToken): bool
    {
        return $this->authTokenService->verify($member, $authToken);
    }

    public function isAuthorizedForDevice(
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        string $authToken,
    ): bool {
        $registrations = WalletPassRegistration::query()
            ->where('device_library_identifier', $deviceLibraryIdentifier)
            ->where('pass_type_identifier', $passTypeIdentifier)
            ->get(['member_id']);

        foreach ($registrations as $registration) {
            $member = Member::find((int) $registration->member_id);
            if ($member !== null && $this->authTokenService->verify($member, $authToken)) {
                return true;
            }
        }

        return false;
    }

    public function registerDevice(
        Member $member,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        string $serialNumber,
        string $pushToken,
    ): void {
        WalletPassRegistration::query()->updateOrCreate(
            [
                'pass_type_identifier' => $passTypeIdentifier,
                'serial_number' => $serialNumber,
                'device_library_identifier' => $deviceLibraryIdentifier,
            ],
            [
                'project_id' => (int) $member->project_id,
                'member_id' => (int) $member->id,
                'push_token' => $pushToken,
            ]
        );

        $this->walletPassSyncService->markMemberUpdated($member);
    }

    public function unregisterDevice(
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        string $serialNumber,
    ): void {
        WalletPassRegistration::query()
            ->where('pass_type_identifier', $passTypeIdentifier)
            ->where('serial_number', $serialNumber)
            ->where('device_library_identifier', $deviceLibraryIdentifier)
            ->delete();
    }

    /**
     * @return array{serialNumbers: list<string>, lastUpdated: string}
     */
    public function listUpdatedSerials(
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        ?string $passesUpdatedSince,
    ): array {
        $rows = WalletPassUpdate::query()
            ->join('wallet_pass_registrations as r', function ($join) use ($deviceLibraryIdentifier, $passTypeIdentifier) {
                $join->on('wallet_pass_updates.serial_number', '=', 'r.serial_number')
                    ->on('wallet_pass_updates.pass_type_identifier', '=', 'r.pass_type_identifier')
                    ->where('r.device_library_identifier', '=', $deviceLibraryIdentifier)
                    ->where('r.pass_type_identifier', '=', $passTypeIdentifier);
            })
            ->where('wallet_pass_updates.pass_type_identifier', $passTypeIdentifier)
            ->select('wallet_pass_updates.serial_number', 'wallet_pass_updates.revision')
            ->orderBy('wallet_pass_updates.revision')->orderBy('wallet_pass_updates.id')->get();
        $cursor = $passesUpdatedSince !== null && str_starts_with($passesUpdatedSince, 'v2:')
            ? filter_var(substr($passesUpdatedSince, 3), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])
            : false;
        // Legacy timestamp tags resync once. Never advance beyond this single snapshot.
        $lastUpdated = max($cursor === false ? 0 : $cursor, (int) ($rows->max('revision') ?? 0));
        $changed = $cursor === false ? $rows : $rows->filter(fn ($row) => (int) $row->revision > $cursor);

        return [
            'serialNumbers' => $changed->pluck('serial_number')->unique()->values()->all(),
            'lastUpdated' => 'v2:'.$lastUpdated,
        ];
    }

    public function generatePassContent(Member $member): ?string
    {
        return $this->walletPassService->generateForMember($member);
    }
}
