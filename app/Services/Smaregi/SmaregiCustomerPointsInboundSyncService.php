<?php

namespace App\Services\Smaregi;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Settings\Repositories\MemberSettings;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Enums\ProfileCustomFieldInputType;
use App\Enums\SmaregiWebhookAction;
use App\Models\Member;
use App\Models\MemberRegistrationAttempt;
use App\Models\Scopes\ProjectScope;
use App\Models\Store;
use App\Models\User;
use App\Services\MemberRankSyncService;
use App\Util\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * スマレジPOS側で変わった会員情報・ポイントを、Webhook経由でローカル会員に反映する。
 */
class SmaregiCustomerPointsInboundSyncService
{
    private const SYNC_DESCRIPTION = 'スマレジPOSからの同期';

    public function __construct(
        private SmaregiApiService $smaregiApi,
        private MemberRepository $memberRepository,
        private MemberSettings $shopSettingRepository,
        private SmaregiPointsSyncService $pointsSync,
        private OrderRepository $orderRepository,
        private DomainEventDispatcher $domainEvents,
        private SmaregiContractProjectResolver $projectResolver,
        private MemberRankSyncService $memberRankSyncService,
        private readonly TenantContext $tenantContext,
        private SmaregiMemberProfileSyncService $profiles,
    ) {}

    /**
     * ローカル会員1件のポイント・プロフィールをスマレジ POS から取り込む（Webhook フォールバック用）。
     */
    public function syncMemberFromSmaregi(Member $member): bool
    {
        $member = $member->fresh();
        if ($member === null) {
            return false;
        }

        $contractId = $this->smaregiApi->getContractIdForProject((int) $member->project_id);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        if ($contractId === '') {
            return false;
        }

        $smaregiCustomerId = trim((string) ($member->smaregi_customer_id ?? ''));
        if ($smaregiCustomerId === '') {
            $smaregiCustomerId = $this->resolveSmaregiCustomerIdByMemberNumber($contractId, $member);
            if ($smaregiCustomerId === '') {
                Log::info('[Smaregi] Inbound member fetch sync skipped: no smaregi_customer_id', [
                    'member_id' => (int) $member->id,
                    'project_id' => (int) $member->project_id,
                    'member_number' => (string) ($member->member_number ?? ''),
                ]);

                return false;
            }
            $member = DB::transaction(function () use ($member, $contractId, $smaregiCustomerId): ?Member {
                $user = User::query()->whereKey($member->user_id)
                    ->where('project_id', $member->project_id)->lockForUpdate()->first();
                if ($user === null) {
                    return null;
                }
                $currentMember = Member::query()->whereKey($member->id)
                    ->where('project_id', $member->project_id)->where('user_id', $member->user_id)
                    ->lockForUpdate()->first();
                if ($currentMember === null
                    || trim((string) $currentMember->smaregi_customer_id) !== ''
                    || $currentMember->member_number !== $member->member_number
                    || $contractId !== trim((string) $this->smaregiApi->getContractIdForProject((int) $member->project_id))
                    || MemberRegistrationAttempt::where('project_id', $member->project_id)->where('user_id', $member->user_id)->exists()) {
                    return null;
                }

                $currentMember->forceFill(['smaregi_customer_id' => $smaregiCustomerId])->save();

                return $currentMember;
            }, 3);
            if ($member === null) {
                return false;
            }
            Log::info('[Smaregi] Linked smaregi_customer_id from member_number', [
                'member_id' => (int) $member->id,
                'member_number' => (string) $member->member_number,
                'smaregi_customer_id' => $smaregiCustomerId,
            ]);
            $this->requestUnsyncedOrderPointsSync($member);
        }

        $this->syncOneCustomer($contractId, $smaregiCustomerId, memberId: (int) $member->id);

        return true;
    }

    private function resolveSmaregiCustomerIdByMemberNumber(string $contractId, Member $member): string
    {
        if (MemberRegistrationAttempt::where('project_id', $member->project_id)->where('user_id', $member->user_id)->exists()) {
            return ''; // Pending registrations require the durable marker, never code-only linking.
        }
        $memberNumber = trim((string) ($member->member_number ?? ''));
        if ($memberNumber === '') {
            return '';
        }

        $remote = $this->smaregiApi->getCustomerByCode($contractId, $memberNumber);
        if (! is_array($remote)) {
            return '';
        }

        $customerId = $remote['customerId'] ?? $remote['customer_id'] ?? null;
        if ($customerId === null || (is_string($customerId) && trim($customerId) === '')) {
            return '';
        }

        return (string) $customerId;
    }

    /**
     * @param  list<string>  $customerIds
     * @param  array<string, int>  $pointsByCustomerId
     */
    public function syncByCustomerIds(
        string $contractId,
        array $customerIds,
        ?string $action,
        array $pointsByCustomerId = [],
    ): void {
        $contractId = trim($contractId);
        if ($contractId === '' || $customerIds === []) {
            return;
        }

        if (SmaregiWebhookAction::isCustomerDeletedAction($action)) {
            Log::info('[Smaregi] Inbound point sync skipped（会員削除系アクションのためポイント同期なし）', [
                'smaregi_contract_id' => $contractId,
                'action' => $action,
            ]);

            return;
        }

        foreach ($customerIds as $rawId) {
            $customerId = $this->normalizeCustomerId($rawId);
            if ($customerId === '') {
                continue;
            }
            $pointsHint = $pointsByCustomerId[$customerId] ?? null;
            $this->syncOneCustomer($contractId, $customerId, is_int($pointsHint) ? $pointsHint : null);
        }
    }

    private function syncOneCustomer(string $contractId, string $smaregiCustomerId, ?int $pointsHint = null, ?int $memberId = null): void
    {
        $projectIds = $this->projectResolver->projectIdsForContract($contractId);
        if ($projectIds === []) {
            Log::info('[Smaregi] Inbound customer sync skipped: no project for contract', [
                'smaregi_contract_id' => $contractId,
                'smaregi_customer_id' => $smaregiCustomerId,
            ]);

            return;
        }

        $members = Member::withoutGlobalScope(ProjectScope::class)
            ->with('project')->whereHas('project')
            ->when($memberId !== null, fn ($query) => $query->whereKey($memberId))
            ->whereIn('project_id', $projectIds)
            ->where('smaregi_customer_id', $smaregiCustomerId)
            ->get();

        if ($members->isEmpty()) {
            Log::info('[Smaregi] Inbound customer sync: ローカル会員なし（smaregi_customer_id 未紐付けまたは別契約）', [
                'smaregi_contract_id' => $contractId,
                'smaregi_customer_id' => $smaregiCustomerId,
                'project_ids_for_contract' => $projectIds,
            ]);

            return;
        }

        $profileSnapshots = [];
        $pointSnapshots = [];
        foreach ($members as $member) {
            $profileSnapshots[(int) $member->id] = $this->profiles->snapshot((int) $member->id);
            $pointSnapshots[(int) $member->id] = [
                'points' => $member->getDisplayPoints(),
                'pending' => $this->pointsSync->hasOutboundPending((int) $member->id)
                    || $this->orderRepository->hasUnsyncedSmaregiPointsForUser((int) $member->user_id, (int) $member->project_id),
            ];
        }
        $remoteCustomer = $this->smaregiApi->getCustomer($contractId, $smaregiCustomerId);
        if ($remoteCustomer === null) {
            Log::warning('[Smaregi] Inbound customer sync: getCustomer failed', [
                'smaregi_contract_id' => $contractId,
                'smaregi_customer_id' => $smaregiCustomerId,
            ]);
        }

        $remotePoints = $pointsHint;
        if ($remotePoints === null) {
            $remotePoint = $this->smaregiApi->getCustomerPoint($contractId, $smaregiCustomerId);
            if ($remotePoint === null) {
                Log::warning('[Smaregi] Inbound customer sync: getCustomerPoint failed', [
                    'smaregi_contract_id' => $contractId,
                    'smaregi_customer_id' => $smaregiCustomerId,
                ]);
            }

            $remotePoints = $remotePoint !== null ? $this->parsePointBalance($remotePoint) : null;
            if ($remotePoints === null && $remoteCustomer !== null) {
                $remotePoints = $this->parsePointBalance($remoteCustomer);
            }
        } else {
            Log::info('[Smaregi] Inbound customer sync: 取引 totalPoint をポイント同期に使用', [
                'smaregi_contract_id' => $contractId,
                'smaregi_customer_id' => $smaregiCustomerId,
                'total_point' => $remotePoints,
            ]);
        }
        if ($remotePoints === null) {
            Log::warning('[Smaregi] Inbound point sync: could not parse point from customer payload', [
                'smaregi_contract_id' => $contractId,
                'smaregi_customer_id' => $smaregiCustomerId,
            ]);
        }

        foreach ($members as $member) {
            $project = $member->project;
            if ($project === null) {
                continue;
            }
            $this->tenantContext->runForProject($project, function () use ($member, $remoteCustomer, $remotePoints, $smaregiCustomerId, $profileSnapshots, $pointSnapshots): void {
                if ($remoteCustomer !== null) {
                    $this->applyRemoteProfileForMember($member, $remoteCustomer, $profileSnapshots[(int) $member->id]);
                }
                if ($remotePoints !== null) {
                    if ($this->pointsSync->hasOutboundPending((int) $member->id)
                        || $this->orderRepository->hasUnsyncedSmaregiPointsForUser((int) $member->user_id, (int) $member->project_id)) {
                        Log::info('[Smaregi] Inbound point sync skipped: outbound pending（ローカル先行の巻き戻し防止）', [
                            'member_id' => (int) $member->id,
                            'project_id' => (int) $member->project_id,
                            'smaregi_customer_id' => $smaregiCustomerId,
                            'remote_points' => $remotePoints,
                        ]);
                    } else {
                        $this->applyRemotePointsForMember($member, $remotePoints, $pointSnapshots[(int) $member->id]);
                        $fresh = $member->fresh();
                        if ($fresh !== null) {
                            $this->memberRankSyncService->sync($fresh);
                        }
                    }
                }
                $this->requestUnsyncedOrderPointsSync($member);
            });
        }
    }

    private function requestUnsyncedOrderPointsSync(Member $member): void
    {
        $userId = (int) ($member->user_id ?? 0);
        $projectId = (int) $member->project_id;
        $smaregiCustomerId = trim((string) ($member->smaregi_customer_id ?? ''));
        if ($userId <= 0 || $smaregiCustomerId === '') {
            return;
        }

        foreach ($this->orderRepository->findUnsyncedSmaregiPointsOrdersForUser($userId, $projectId) as $order) {
            $this->domainEvents->dispatch(new OrderPointsSyncRequested(
                $order['orderId'],
                $order['projectId'],
                $order['userId'],
            ));
        }
    }

    /**
     * @param  array<string, string|int|float|bool|array<string, string|int|float|bool|null>|null>  $remoteCustomer
     * @param  array{token: string, delivered: string|null}|null  $snapshot
     */
    private function applyRemoteProfileForMember(Member $member, array $remoteCustomer, ?array $snapshot): void
    {
        DB::transaction(function () use ($member, $remoteCustomer, $snapshot): void {
            $user = User::withoutGlobalScopes()->whereKey($member->user_id)->where('project_id', $member->project_id)
                ->lockForUpdate()->first();
            $locked = Member::query()->with('user')->whereKey($member->id)->lockForUpdate()->first();
            if ($locked === null) {
                return;
            }
            if ((int) $locked->user_id !== (int) ($user?->id ?? 0)
                || (string) $locked->smaregi_customer_id !== (string) $member->smaregi_customer_id
                || ! $this->profiles->canApplyInbound((int) $locked->id, $snapshot)) {
                return;
            }
            $locked->setRelation('user', $user);
            $this->applyProfileForMember($locked, $remoteCustomer);
        });
    }

    /** @param array{points: int, pending: bool} $snapshot */
    private function applyRemotePointsForMember(Member $member, int $remotePoints, array $snapshot): void
    {
        DB::transaction(function () use ($member, $remotePoints, $snapshot): void {
            $memberId = (int) $member->id;
            $projectId = (int) $member->project_id;

            $this->memberRepository->syncPointsLedger($memberId, $projectId);

            $aggregate = $this->memberRepository->findByIdForUpdate($memberId, $projectId);
            if ($aggregate === null) {
                return;
            }

            $localSum = $aggregate->displayPoints();
            if ($snapshot['pending'] || $localSum !== $snapshot['points']
                || $aggregate->smaregiCustomerId() !== (string) $member->smaregi_customer_id
                || $this->pointsSync->hasOutboundPending($memberId)
                || $this->orderRepository->hasUnsyncedSmaregiPointsForUser((int) $member->user_id, $projectId)) {
                Log::info('[Smaregi] Inbound point sync skipped: local state changed during remote fetch', [
                    'member_id' => $memberId, 'project_id' => $projectId,
                ]);

                return;
            }
            $delta = $remotePoints - $localSum;
            if ($delta === 0) {
                Log::info('[Smaregi] Inbound customer sync: ポイントはスマレジとローカル一致のため更新なし', [
                    'member_id' => $aggregate->id()->value(),
                    'project_id' => $aggregate->projectId(),
                    'points' => $remotePoints,
                ]);

                return;
            }

            if ($delta > 0) {
                $validDays = $this->shopSettingRepository->getPointsValidDays($aggregate->projectId());
                $aggregate->earnPoints(
                    $delta,
                    self::SYNC_DESCRIPTION,
                    null,
                    now()->addDays($validDays),
                );
            } else {
                $aggregate->usePoints(abs($delta), self::SYNC_DESCRIPTION, null);
            }

            $this->memberRepository->save($aggregate);

            Log::info('[Smaregi] Inbound point sync applied', [
                'member_id' => $aggregate->id()->value(),
                'project_id' => $aggregate->projectId(),
                'local_sum_before' => $localSum,
                'remote_points' => $remotePoints,
                'delta' => $delta,
            ]);
        });
    }

    /**
     * @param  string|int|float|bool|array<string, string|int|float|bool|null>|null  $rawId
     */
    private function normalizeCustomerId($rawId): string
    {
        if (is_string($rawId)) {
            return trim($rawId);
        }
        if (is_int($rawId)) {
            return (string) $rawId;
        }

        return '';
    }

    /**
     * @param  array<string, string|int|float|bool|array<string, string|int|float|bool|null>|null>  $customer
     */
    private function parsePointBalance(array $customer): ?int
    {
        foreach (['point', 'points', 'Point', 'Points'] as $key) {
            if (! array_key_exists($key, $customer)) {
                continue;
            }
            $v = $customer[$key];
            if ($v === null) {
                return 0;
            }
            if (is_int($v)) {
                return $v;
            }
            if (is_float($v)) {
                return (int) round($v);
            }
            if (is_string($v) && $v !== '' && is_numeric($v)) {
                return (int) round((float) $v);
            }
            if (is_string($v) && $v === '') {
                return 0;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string|int|float|bool|array<string, string|int|float|bool|null>|null>  $customer
     */
    private function applyProfileForMember(Member $member, array $customer): void
    {
        $memberUpdates = $this->buildMemberUpdates($member, $customer);
        if ($memberUpdates !== []) {
            $member->update($memberUpdates);
        }

        $user = $member->user;
        if ($user === null) {
            return;
        }
        $userUpdates = $this->buildUserUpdates($customer);
        if ($userUpdates !== []) {
            $user->update($userUpdates);
        }
    }

    /**
     * @param  array<string, string|int|float|bool|array<string, string|int|float|bool|null>|null>  $customer
     * @return array<string, string|array<string, string|list<string>|null>|null>
     */
    private function buildMemberUpdates(Member $member, array $customer): array
    {
        $updates = [];
        $customerCode = $this->stringOrNull($customer['customerCode'] ?? null);
        if ($customerCode !== null && $customerCode !== (string) $member->member_number && ! $this->memberNumberExists($member, $customerCode)) {
            $updates['member_number'] = $customerCode;
        }
        if (array_key_exists('postCode', $customer)) {
            $updates['postal_code'] = $this->stringOrNull($customer['postCode']);
        }
        if (array_key_exists('address', $customer)) {
            $updates['address'] = $this->stringOrNull($customer['address']);
        }
        if (array_key_exists('birthDate', $customer)) {
            $updates['birthday'] = $this->stringOrNull($customer['birthDate']);
        }
        if (array_key_exists('sex', $customer)) {
            $updates['sex'] = $this->stringOrNull($customer['sex']);
        }
        if (array_key_exists('mailReceiveFlag', $customer)) {
            $updates['mail_receive_flag'] = $this->stringOrNull($customer['mailReceiveFlag']);
        }
        if (isset($customer['storeId']) && (string) $customer['storeId'] !== '') {
            $storeId = Store::where('project_id', $member->project_id)->where('smaregi_store_id', (string) $customer['storeId'])->value('id');
            if ($storeId !== null) {
                $updates['customer_store_id'] = (int) $storeId;
            }
        }
        $extraUpdates = $this->profileExtraFromRemoteCustomFields($member, $customer);
        if ($extraUpdates !== []) {
            $updates['profile_extra'] = $extraUpdates;
        }

        return $updates;
    }

    /**
     * @param  array<string, string|int|float|bool|array<string, string|int|float|bool|null>|null>  $customer
     * @return array<string, string|list<string>|null>
     */
    private function profileExtraFromRemoteCustomFields(Member $member, array $customer): array
    {
        $remoteFields = $customer['customFields'] ?? null;
        if (! is_array($remoteFields) || $remoteFields === []) {
            return [];
        }
        $defs = $this->shopSettingRepository->getProfileFieldSettings((int) $member->project_id)['custom_fields'] ?? [];
        $keyByNo = [];
        $typeByKey = [];
        foreach ($defs as $cf) {
            if (! is_array($cf)) {
                continue;
            }
            $no = (int) ($cf['smaregi_no'] ?? 0);
            $key = (string) ($cf['key'] ?? '');
            if ($no < 1 || $key === '') {
                continue;
            }
            $keyByNo[$no] = $key;
            $typeByKey[$key] = (string) ($cf['input_type'] ?? '');
        }
        if ($keyByNo === []) {
            return [];
        }
        $extra = is_array($member->profile_extra) ? $member->profile_extra : [];
        $changed = false;
        foreach ($remoteFields as $field) {
            if (! is_array($field)) {
                continue;
            }
            $key = $keyByNo[(int) ($field['no'] ?? 0)] ?? '';
            if ($key === '') {
                continue;
            }
            $value = $this->fromSmaregiCustomFieldValue(
                $this->stringOrNull($field['value'] ?? null),
                $typeByKey[$key] ?? '',
            );
            if (($extra[$key] ?? null) === $value) {
                continue;
            }
            $extra[$key] = $value;
            $changed = true;
        }

        return $changed ? $extra : [];
    }

    /**
     * @return string|list<string>|null
     */
    private function fromSmaregiCustomFieldValue(?string $value, string $inputType): string|array|null
    {
        if ($value === null) {
            return null;
        }
        if ($inputType !== ProfileCustomFieldInputType::CHECKBOX) {
            return $value;
        }

        return array_values(array_filter(
            explode('、', $value),
            static fn (string $part): bool => $part !== '',
        ));
    }

    /**
     * @param  array<string, string|int|float|bool|array<string, string|int|float|bool|null>|null>  $customer
     * @return array<string, string|null>
     */
    private function buildUserUpdates(array $customer): array
    {
        $updates = [];
        $name = $this->joinCustomerName($customer, 'lastName', 'firstName');
        if ($name !== null) {
            $updates['name'] = $name;
        }
        $kana = $this->joinCustomerName($customer, 'lastKana', 'firstKana');
        if ($kana !== null) {
            $updates['kana'] = $kana;
        }
        if (array_key_exists('mailAddress', $customer)) {
            $updates['email'] = $this->stringOrNull($customer['mailAddress']);
        }
        if (array_key_exists('phoneNumber', $customer)) {
            $updates['phone'] = $this->stringOrNull($customer['phoneNumber']);
        }

        return $updates;
    }

    /**
     * @param  array<string, string|int|float|bool|array<string, string|int|float|bool|null>|null>  $customer
     */
    private function joinCustomerName(array $customer, string $lastKey, string $firstKey): ?string
    {
        if (! array_key_exists($lastKey, $customer) && ! array_key_exists($firstKey, $customer)) {
            return null;
        }
        $last = $this->stringOrNull($customer[$lastKey] ?? null);
        $first = $this->stringOrNull($customer[$firstKey] ?? null);
        $joined = trim(implode(' ', array_filter(
            [$last, $first],
            fn ($value) => $value !== null && ! in_array($value, [SmaregiCustomerUpdatePayloadBuilder::NAME_PLACEHOLDER, SmaregiCustomerUpdatePayloadBuilder::KANA_PLACEHOLDER], true),
        )));

        return $joined !== '' ? $joined : null;
    }

    /**
     * @param  string|int|float|bool|array<string, string|int|float|bool|null>|null  $value
     */
    private function stringOrNull($value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value !== '' ? $value : null;
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }

    private function memberNumberExists(Member $member, string $memberNumber): bool
    {
        return Member::query()
            ->where('project_id', $member->project_id)
            ->where('member_number', $memberNumber)
            ->where('id', '!=', $member->id)
            ->exists();
    }
}
