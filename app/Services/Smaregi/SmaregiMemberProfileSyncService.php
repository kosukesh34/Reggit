<?php

namespace App\Services\Smaregi;

use App\Domain\Settings\Repositories\MemberSettings;
use App\Models\Member;
use App\Models\Project;
use App\Models\SmaregiMemberProfileSync;
use App\Models\User;
use App\Services\CustomerStoreProfile;
use App\Util\AdminContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SmaregiMemberProfileSyncService
{
    public function __construct(
        private SmaregiApiService $api,
        private SmaregiCustomerUpdatePayloadBuilder $payloads,
        private CustomerStoreProfile $stores,
        private MemberSettings $settings,
    ) {}

    /** The caller holds the user/member locks and saves this request in the same transaction. */
    /** @param list<string> $changedKeys */
    public function record(Member $member, User $user, array $changedKeys, bool $immediate = true): void
    {
        $customerId = trim((string) $member->smaregi_customer_id);
        if ($customerId === '') {
            return;
        }
        $projectId = (int) $member->project_id;
        $previous = SmaregiMemberProfileSync::whereKey($member->id)->lockForUpdate()->first();
        $keys = array_values(array_unique(array_merge($changedKeys,
            $previous !== null && $previous->delivered_at === null ? $previous->field_keys : [])));
        $data = array_merge($user->only(['name', 'kana', 'email', 'phone']), $member->only([
            'birthday', 'address', 'postal_code', 'prefecture', 'city', 'street', 'sex',
            'mail_receive_flag', 'customer_store_id',
        ]), $member->profile_extra ?? []);
        if (array_intersect($keys, ['address', 'prefecture', 'city', 'street']) !== []) {
            $keys = array_values(array_unique(array_merge($keys, ['prefecture', 'city', 'street'])));
            if (! in_array('address', $keys, true)
                || (! in_array('address', $changedKeys, true)
                    && array_intersect($changedKeys, ['prefecture', 'city', 'street']) !== [])) {
                $keys = array_values(array_diff($keys, ['address']));
            }
        }
        $data = array_intersect_key($data, array_flip($keys));
        $body = $this->payloads->buildFromProfileData($this->stores->forSmaregi($data, $projectId),
            $this->settings->getProfileFieldSettings($projectId)['custom_fields'] ?? []);
        SmaregiMemberProfileSync::upsert([[
            'member_id' => $member->id, 'project_id' => $projectId, 'customer_id' => $customerId,
            'contract_id' => trim((string) $this->api->getContractIdForProject($projectId)),
            'body' => json_encode($body, JSON_THROW_ON_ERROR), 'field_keys' => json_encode($keys, JSON_THROW_ON_ERROR), 'request_token' => (string) Str::uuid(),
            'retry_at' => now(), 'delivered_at' => $body === [] ? now() : null,
        ]], ['member_id'], ['project_id', 'customer_id', 'contract_id', 'body', 'field_keys', 'request_token', 'retry_at', 'delivered_at']);
        if ($immediate && $body !== []) {
            DB::afterCommit(function () use ($member): void {
                try {
                    $this->retryPending((int) $member->id);
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });
        }
    }

    public function retryPending(int $memberId): bool
    {
        $lease = (string) Str::uuid();
        $request = DB::transaction(function () use ($memberId, $lease): ?SmaregiMemberProfileSync {
            $row = SmaregiMemberProfileSync::whereKey($memberId)->whereNull('delivered_at')
                ->where('retry_at', '<=', now())
                ->where(fn ($query) => $query->whereNull('leased_until')->orWhere('leased_until', '<=', now()))
                ->lockForUpdate()->first();
            if ($row !== null) {
                $row->update(['lease_token' => $lease, 'leased_until' => now()->addMinutes(5)]);
            }

            return $row;
        });
        if ($request === null) {
            return true;
        }
        $owned = fn () => SmaregiMemberProfileSync::whereKey($memberId)
            ->where('request_token', $request->request_token)->where('lease_token', $lease);
        $previous = AdminContext::getCurrentProject();
        $attempted = false;
        $settled = false;
        try {
            AdminContext::setCurrentProject(Project::findOrFail($request->project_id));
            $member = Member::withoutGlobalScopes()->whereKey($memberId)
                ->where('project_id', $request->project_id)->whereNull('deleted_at')->first();
            if ($member === null || trim((string) $member->smaregi_customer_id) !== $request->customer_id) {
                $settled = $owned()->update(['delivered_at' => now()]) > 0;

                return true;
            }
            $contract = trim((string) $this->api->getContractIdForProject((int) $request->project_id));
            if ($contract !== '' && $request->contract_id !== '' && $contract !== $request->contract_id) {
                $settled = $owned()->update(['delivered_at' => now()]) > 0;

                return true;
            }
            if ($contract === '' || $request->contract_id === '') {
                $settled = $owned()->update(['retry_at' => now()->addHour()]) > 0;

                return false;
            }
            $this->api->clearLastErrorDetail();
            $attempted = true;
            $result = $this->api->updateCustomer($contract, $request->customer_id, $request->body);
            if ($result === null) {
                $settled = $owned()->update(['retry_at' => now()->addMinute()]) > 0;

                return false;
            }
            $settled = $owned()->update(['delivered_at' => now()]) > 0;
        } catch (\Throwable $exception) {
            report($exception);
            $settled = $owned()->update(['retry_at' => now()->addMinute()]) > 0;

            return false;
        } finally {
            AdminContext::setCurrentProject($previous);
            SmaregiMemberProfileSync::whereKey($memberId)->where('lease_token', $lease)
                ->update(['leased_until' => null, 'lease_token' => null]);
            if ($attempted && ! $settled) {
                // A late HTTP write may undo a newer ACK; rebuild from current local state.
                $this->requestCurrentProfile($memberId, (int) $request->project_id, $request->field_keys, $request->customer_id, $request->contract_id);
            }
        }

        return true;
    }

    /** @param list<string> $keys */
    private function requestCurrentProfile(int $memberId, int $projectId, array $keys, string $customerId, string $contractId): void
    {
        $member = Member::withoutGlobalScopes()->whereKey($memberId)->where('project_id', $projectId)
            ->whereNull('deleted_at')->first();
        if ($member === null) {
            return;
        }
        $previous = AdminContext::getCurrentProject();
        try {
            AdminContext::setCurrentProject(Project::findOrFail($projectId));
            DB::transaction(function () use ($member, $projectId, $keys, $customerId, $contractId): void {
                $user = User::withoutGlobalScopes()->whereKey($member->user_id)->where('project_id', $projectId)
                    ->lockForUpdate()->first();
                $current = Member::withoutGlobalScopes()->whereKey($member->id)->where('project_id', $projectId)
                    ->whereNull('deleted_at')->lockForUpdate()->first();
                if ($user !== null && $current !== null && (int) $current->user_id === (int) $user->id
                    && trim((string) $current->smaregi_customer_id) === $customerId
                    && trim((string) $this->api->getContractIdForProject($projectId)) === $contractId) {
                    $latest = SmaregiMemberProfileSync::whereKey($current->id)->lockForUpdate()->first();
                    $this->record($current, $user, array_values(array_unique(array_merge($keys, $latest?->field_keys ?? []))), false);
                }
            });
        } finally {
            AdminContext::setCurrentProject($previous);
        }
    }

    /** @return array{token: string, delivered: string|null}|null */
    public function snapshot(int $memberId, bool $lock = false): ?array
    {
        $row = SmaregiMemberProfileSync::whereKey($memberId)->when($lock, fn ($query) => $query->lockForUpdate())->first();

        return $row === null ? null : ['token' => (string) $row->request_token,
            'delivered' => $row->delivered_at?->toDateTimeString()];
    }

    /** @param array{token: string, delivered: string|null}|null $snapshot */
    public function canApplyInbound(int $memberId, ?array $snapshot): bool
    {
        $current = $this->snapshot($memberId, true);

        return $current === $snapshot && ($current === null || $current['delivered'] !== null);
    }
}
