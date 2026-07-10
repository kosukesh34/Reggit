<?php

namespace App\Services\Smaregi;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Models\Member;
use App\Models\Project;
use App\Models\Settings\ProjectProfileSmaregiSetting;
use App\Models\SmaregiContract;
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
        private ShopSettingRepository $shopSettingRepository,
        private SmaregiPointsSyncService $pointsSync,
        private OrderRepository $orderRepository,
        private DomainEventDispatcher $domainEvents,
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
            $member->forceFill(['smaregi_customer_id' => $smaregiCustomerId])->save();
            $this->requestUnsyncedOrderPointsSync($member);
        }

        $this->syncOneCustomer($contractId, $smaregiCustomerId);

        return true;
    }

    private function resolveSmaregiCustomerIdByMemberNumber(string $contractId, Member $member): string
    {
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

        Log::info('[Smaregi] Linked smaregi_customer_id from member_number', [
            'member_id' => (int) $member->id,
            'member_number' => $memberNumber,
            'smaregi_customer_id' => (string) $customerId,
        ]);

        return (string) $customerId;
    }

    /**
     * @param  list<string>  $customerIds
     */
    public function syncByCustomerIds(string $contractId, array $customerIds, ?string $action): void
    {
        $contractId = trim($contractId);
        if ($contractId === '' || $customerIds === []) {
            return;
        }

        $actionLower = $action !== null ? strtolower($action) : '';
        if (in_array($actionLower, ['deleted', 'bulk-deleted'], true)) {
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
            $this->syncOneCustomer($contractId, $customerId);
        }
    }

    private function syncOneCustomer(string $contractId, string $smaregiCustomerId): void
    {
        $projectIds = $this->projectIdsForContract($contractId);
        if ($projectIds === []) {
            Log::info('[Smaregi] Inbound customer sync skipped: no project for contract', [
                'smaregi_contract_id' => $contractId,
                'smaregi_customer_id' => $smaregiCustomerId,
            ]);

            return;
        }

        $members = Member::query()
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

        $remoteCustomer = $this->smaregiApi->getCustomer($contractId, $smaregiCustomerId);
        if ($remoteCustomer === null) {
            Log::warning('[Smaregi] Inbound customer sync: getCustomer failed', [
                'smaregi_contract_id' => $contractId,
                'smaregi_customer_id' => $smaregiCustomerId,
            ]);
        }

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
        if ($remotePoints === null) {
            Log::warning('[Smaregi] Inbound point sync: could not parse point from customer payload', [
                'smaregi_contract_id' => $contractId,
                'smaregi_customer_id' => $smaregiCustomerId,
            ]);
        }

        foreach ($members as $member) {
            if ($remoteCustomer !== null) {
                $this->applyRemoteProfileForMember($member, $remoteCustomer);
            }
            if ($remotePoints !== null) {
                if ($this->pointsSync->hasOutboundPending((int) $member->id)) {
                    Log::info('[Smaregi] Inbound point sync skipped: outbound pending（ローカル先行の巻き戻し防止）', [
                        'member_id' => (int) $member->id,
                        'project_id' => (int) $member->project_id,
                        'smaregi_customer_id' => $smaregiCustomerId,
                        'remote_points' => $remotePoints,
                    ]);
                } else {
                    $this->applyRemotePointsForMember($member, $remotePoints);
                }
            }
            $this->requestUnsyncedOrderPointsSync($member);
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
     * @return list<int>
     */
    private function projectIdsForContract(string $contractId): array
    {
        $contractId = trim($contractId);
        $fromSettings = ProjectProfileSmaregiSetting::query()
            ->where('smaregi_contract_id', $contractId)
            ->pluck('project_id')
            ->all();
        $fromContracts = SmaregiContract::query()
            ->where('contract_id', $contractId)
            ->whereNotNull('project_id')
            ->pluck('project_id')
            ->all();

        $ids = array_values(array_unique(array_map('intval', array_merge($fromSettings, $fromContracts))));
        if ($ids !== []) {
            return $ids;
        }

        // ShopSetting / SmaregiContract 未登録でも config フォールバックで契約を使うプロジェクトを含める
        $fromEffectiveContract = [];
        foreach (Project::query()->pluck('id') as $projectId) {
            $effective = $this->smaregiApi->getContractIdForProject((int) $projectId);
            if (is_string($effective) && trim($effective) === $contractId) {
                $fromEffectiveContract[] = (int) $projectId;
            }
        }

        return array_values(array_unique($fromEffectiveContract));
    }

    /**
     * @param  array<string, string|int|float|bool|array<string, string|int|float|bool|null>|null>  $remoteCustomer
     */
    private function applyRemoteProfileForMember(Member $member, array $remoteCustomer): void
    {
        DB::transaction(function () use ($member, $remoteCustomer): void {
            $locked = Member::query()->with('user')->whereKey($member->id)->lockForUpdate()->first();
            if ($locked === null) {
                return;
            }
            $this->applyProfileForMember($locked, $remoteCustomer);
        });
    }

    private function applyRemotePointsForMember(Member $member, int $remotePoints): void
    {
        DB::transaction(function () use ($member, $remotePoints): void {
            $memberId = (int) $member->id;
            $projectId = (int) $member->project_id;

            $this->memberRepository->syncPointsLedger($memberId, $projectId);

            $aggregate = $this->memberRepository->findByIdForUpdate($memberId, $projectId);
            if ($aggregate === null) {
                return;
            }

            $localSum = $aggregate->displayPoints();
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
     * @return array<string, string|null>
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

        return $updates;
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
        $joined = trim(implode(' ', array_filter([$last, $first], fn ($value) => $value !== null && $value !== '')));

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
