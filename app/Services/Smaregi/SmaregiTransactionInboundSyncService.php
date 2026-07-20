<?php

namespace App\Services\Smaregi;

use App\Domain\Member\Events\PointsEarned;
use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Policies\StampEarnPolicy;
use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Enums\MemberVisitSource;
use App\Enums\PointTransactionType;
use App\Models\Member;
use App\Models\MemberStampTransaction;
use App\Models\MemberVisit;
use App\Models\MemberVisitItem;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\Project;
use App\Models\Settings\ProjectProfileSmaregiSetting;
use App\Models\SmaregiContract;
use App\Services\Admin\MemberAdminService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * スマレジ POS の通常販売取引を来店・購入履歴として取り込む。
 * 取消・返品時は付与済みポイント／スタンプ／来店を巻き戻す。
 */
class SmaregiTransactionInboundSyncService
{
    private const SALE_HEAD_DIVISION = '1';

    private const CANCEL_DIVISION_NORMAL = '0';

    private const CANCEL_DIVISION_CANCELLED = '1';

    private const RETURN_SALES_YES = '1';

    public function __construct(
        private SmaregiApiService $smaregiApi,
        private MemberAdminService $memberAdminService,
        private ShopSettingRepository $shopSettingRepository,
        private StampEarnPolicy $stampEarnPolicy,
        private MemberRepository $memberRepository,
        private DomainEventDispatcher $domainEvents,
        private SmaregiPointsSyncService $pointsSync,
    ) {}

    /**
     * @param  list<string>  $transactionHeadIds
     */
    public function syncByTransactionHeadIds(string $contractId, array $transactionHeadIds): void
    {
        $contractId = trim($contractId);
        if ($contractId === '' || $transactionHeadIds === []) {
            return;
        }

        foreach ($transactionHeadIds as $rawHeadId) {
            $headId = trim((string) $rawHeadId);
            if ($headId === '') {
                continue;
            }
            $this->syncOneTransaction($contractId, $headId);
        }
    }

    private function syncOneTransaction(string $contractId, string $headId): void
    {
        $transaction = $this->smaregiApi->getTransaction($contractId, $headId, true);
        if ($transaction === null) {
            Log::warning('[Smaregi] Inbound transaction sync: getTransaction failed', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        if ($this->isCancelledTransaction($transaction)) {
            $this->handleCancelledTransaction($contractId, $headId, $transaction);

            return;
        }

        if (! $this->isNormalSaleTransaction($transaction)) {
            Log::info('[Smaregi] Inbound transaction sync skipped: not a normal sale', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
                'transaction_head_division' => $transaction['transactionHeadDivision'] ?? null,
                'cancel_division' => $transaction['cancelDivision'] ?? null,
            ]);

            return;
        }

        $customerId = $this->extractCustomerId($transaction);
        if ($customerId === '') {
            Log::info('[Smaregi] Inbound transaction sync skipped: no customerId', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        $amount = $this->resolvePurchaseAmount($transaction);
        if ($amount === null) {
            Log::info('[Smaregi] Inbound transaction sync skipped: no purchase amount', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        $visitedAt = $this->resolveVisitedAt($transaction);
        if ($visitedAt === null) {
            Log::warning('[Smaregi] Inbound transaction sync skipped: no transaction datetime', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        $members = $this->findMembers($contractId, $customerId);
        if ($members === []) {
            Log::info('[Smaregi] Inbound transaction sync: ローカル会員なし', [
                'smaregi_contract_id' => $contractId,
                'smaregi_customer_id' => $customerId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        $isReturn = $this->isReturnSalesTransaction($transaction);
        foreach ($members as $member) {
            if ($isReturn) {
                $this->handleReturnTransaction($member, $headId, $visitedAt, abs($amount), $transaction);
            } else {
                $this->recordVisitWithItems($member, $headId, $visitedAt, $amount, $transaction);
            }
        }
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function isCancelledTransaction(array $transaction): bool
    {
        $cancel = trim((string) ($transaction['cancelDivision'] ?? self::CANCEL_DIVISION_NORMAL));

        return $cancel === self::CANCEL_DIVISION_CANCELLED;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function isReturnSalesTransaction(array $transaction): bool
    {
        return trim((string) ($transaction['returnSales'] ?? '0')) === self::RETURN_SALES_YES;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function handleCancelledTransaction(string $contractId, string $headId, array $transaction): void
    {
        $customerId = $this->extractCustomerId($transaction);
        if ($customerId === '') {
            Log::info('[Smaregi] Inbound transaction cancel skipped: no customerId', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        $members = $this->findMembers($contractId, $customerId);
        if ($members === []) {
            return;
        }

        foreach ($members as $member) {
            $this->reverseAwardsForTransaction($member, $headId, '取消');
            $this->deleteVisitForTransaction($member, $headId);
            Log::info('[Smaregi] Inbound transaction cancel reversed', [
                'member_id' => (int) $member->id,
                'project_id' => (int) $member->project_id,
                'transaction_head_id' => $headId,
            ]);
        }
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function handleReturnTransaction(
        Member $member,
        string $headId,
        Carbon $visitedAt,
        float $absAmount,
        array $transaction,
    ): void {
        $this->recordVisitWithItems($member, $headId, $visitedAt, -1 * $absAmount, $transaction);
        // スマレジ側でポイント増減済みならローカル再計算で減算しない（会員ポイント inbound に任せる）
        if (! $this->hasSmaregiManagedPointDelta($transaction)) {
            $this->reversePointsByAmount($member, $absAmount, $headId, '返品');
        }
        $this->reverseStampsByAmount($member, $absAmount, $headId, '返品');
    }

    private function reverseAwardsForTransaction(Member $member, string $headId, string $reasonLabel): void
    {
        $this->reverseAwardedPoints($member, $headId, $reasonLabel);
        $this->reverseAwardedStamps($member, $headId, $reasonLabel);
    }

    private function reverseAwardedPoints(Member $member, string $headId, string $reasonLabel): void
    {
        $earnDescription = $this->pointAwardDescription($headId);
        $reverseDescription = $this->pointReverseDescription($headId, $reasonLabel);
        if ($this->hasPointTransaction($member, PointTransactionType::USED, $reverseDescription)
            || $this->hasPointTransaction($member, PointTransactionType::REVERSED, $reverseDescription)) {
            return;
        }

        $earned = PointTransaction::query()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->where('type', PointTransactionType::EARNED)
            ->where('description', $earnDescription)
            ->sum('points');
        $points = (int) $earned;
        if ($points <= 0) {
            return;
        }

        $this->applyPointReversal($member, $points, $reverseDescription);
    }

    private function reversePointsByAmount(Member $member, float $absAmount, string $headId, string $reasonLabel): void
    {
        if ($absAmount <= 0) {
            return;
        }
        $reverseDescription = $this->pointReverseDescription($headId, $reasonLabel);
        if ($this->hasPointTransaction($member, PointTransactionType::USED, $reverseDescription)
            || $this->hasPointTransaction($member, PointTransactionType::REVERSED, $reverseDescription)) {
            return;
        }

        $projectId = (int) $member->project_id;
        $rankKey = is_string($member->rank) ? $member->rank : null;
        $rate = $this->shopSettingRepository->getPointEarnRateForRank($projectId, $rankKey);
        if ($rate <= 0) {
            return;
        }
        $points = (int) floor($absAmount / $rate);
        if ($points <= 0) {
            return;
        }

        $this->applyPointReversal($member, $points, $reverseDescription);
    }

    private function applyPointReversal(Member $member, int $points, string $description): void
    {
        $projectId = (int) $member->project_id;
        $this->pointsSync->markOutboundPending((int) $member->id);

        $reversed = DB::transaction(function () use ($member, $projectId, $points, $description): int {
            $this->memberRepository->syncPointsLedger((int) $member->id, $projectId);
            $aggregate = $this->memberRepository->findByIdForUpdate((int) $member->id, $projectId);
            if ($aggregate === null) {
                return 0;
            }
            if ($this->hasPointTransaction($member, PointTransactionType::USED, $description)
                || $this->hasPointTransaction($member, PointTransactionType::REVERSED, $description)) {
                return 0;
            }
            $aggregate->reverseEarnedPoints($points, $description, null);
            $this->memberRepository->save($aggregate);

            return $points;
        });

        if ($reversed <= 0) {
            $this->pointsSync->clearOutboundPending((int) $member->id);

            return;
        }

        $this->domainEvents->dispatch(new PointsEarned(
            (int) $member->id,
            $projectId,
            -1 * $reversed,
            null,
            true,
        ));
    }

    private function reverseAwardedStamps(Member $member, string $headId, string $reasonLabel): void
    {
        $earnDescription = $this->stampAwardDescription($headId);
        $reverseDescription = $this->stampReverseDescription($headId, $reasonLabel);
        if ($this->hasStampTransaction($member, $reverseDescription)) {
            return;
        }

        $earned = MemberStampTransaction::query()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->where('description', $earnDescription)
            ->where('delta', '>', 0)
            ->get();
        if ($earned->isEmpty()) {
            return;
        }

        foreach ($earned as $tx) {
            $delta = (int) $tx->delta;
            if ($delta <= 0) {
                continue;
            }
            $this->memberAdminService->adjustStampsToMember(
                $member,
                (int) $tx->stamp_card_id,
                -1 * $delta,
                $reverseDescription,
                null
            );
        }
    }

    private function reverseStampsByAmount(Member $member, float $absAmount, string $headId, string $reasonLabel): void
    {
        if ($absAmount <= 0) {
            return;
        }
        $reverseDescription = $this->stampReverseDescription($headId, $reasonLabel);
        if ($this->hasStampTransaction($member, $reverseDescription)) {
            return;
        }

        $projectId = (int) $member->project_id;
        $stampCardId = $this->shopSettingRepository->getOrderStampCardId($projectId);
        $stampEarnPerAmount = $this->shopSettingRepository->getStampEarnPerAmount($projectId);
        if ($stampCardId === null || $stampEarnPerAmount <= 0) {
            return;
        }
        $stampCount = $this->stampEarnPolicy->calculateCount($absAmount, $stampEarnPerAmount);
        if ($stampCount <= 0) {
            return;
        }

        $this->memberAdminService->adjustStampsToMember(
            $member,
            $stampCardId,
            -1 * $stampCount,
            $reverseDescription,
            null
        );
    }

    private function deleteVisitForTransaction(Member $member, string $headId): void
    {
        $visit = MemberVisit::withoutGlobalScopes()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->where('smaregi_transaction_head_id', $headId)
            ->first();
        if ($visit === null) {
            return;
        }
        $visit->items()->delete();
        $visit->delete();
    }

    private function pointReverseDescription(string $headId, string $reasonLabel): string
    {
        return 'スマレジ取引'.$reasonLabel.'によるポイント取消 #'.$headId;
    }

    private function stampReverseDescription(string $headId, string $reasonLabel): string
    {
        return 'スマレジ取引'.$reasonLabel.'によるスタンプ取消 #'.$headId;
    }

    private function hasPointTransaction(Member $member, string $type, string $description): bool
    {
        return PointTransaction::query()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->where('type', $type)
            ->where('description', $description)
            ->exists();
    }

    private function hasStampTransaction(Member $member, string $description): bool
    {
        return MemberStampTransaction::query()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->where('description', $description)
            ->exists();
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function isNormalSaleTransaction(array $transaction): bool
    {
        $division = trim((string) ($transaction['transactionHeadDivision'] ?? ''));
        if ($division !== self::SALE_HEAD_DIVISION) {
            return false;
        }

        $cancelDivision = $transaction['cancelDivision'] ?? self::CANCEL_DIVISION_NORMAL;
        $cancel = trim((string) $cancelDivision);

        return $cancel === '' || $cancel === self::CANCEL_DIVISION_NORMAL;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function extractCustomerId(array $transaction): string
    {
        $customerId = $transaction['customerId'] ?? $transaction['customer_id'] ?? null;
        if ($customerId === null || trim((string) $customerId) === '') {
            $nested = $transaction['customer'] ?? null;
            if (is_array($nested)) {
                $customerId = $nested['customerId'] ?? $nested['customer_id'] ?? null;
            }
        }
        if ($customerId === null) {
            return '';
        }

        return trim((string) $customerId);
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function resolvePurchaseAmount(array $transaction): ?float
    {
        $raw = $transaction['total'] ?? $transaction['subtotal'] ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }
        if (! is_numeric($raw)) {
            return null;
        }
        $amount = (float) $raw;
        $returnSales = trim((string) ($transaction['returnSales'] ?? '0'));
        if ($returnSales === '1') {
            return -1 * abs($amount);
        }

        return $amount;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function resolveVisitedAt(array $transaction): ?Carbon
    {
        foreach (['terminalTranDateTime', 'transactionDateTime'] as $key) {
            $raw = $transaction[$key] ?? null;
            if (! is_string($raw) || trim($raw) === '') {
                continue;
            }
            try {
                return Carbon::parse($raw);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @return list<Member>
     */
    private function findMembers(string $contractId, string $smaregiCustomerId): array
    {
        $projectIds = $this->projectIdsForContract($contractId);
        if ($projectIds === []) {
            return [];
        }

        return Member::query()
            ->whereIn('project_id', $projectIds)
            ->where('smaregi_customer_id', $smaregiCustomerId)
            ->get()
            ->all();
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function recordVisitWithItems(
        Member $member,
        string $headId,
        Carbon $visitedAt,
        float $amount,
        array $transaction,
    ): void {
        $visit = MemberVisit::withoutGlobalScopes()
            ->where('project_id', $member->project_id)
            ->where('smaregi_transaction_head_id', $headId)
            ->first();

        if ($visit === null) {
            try {
                $visit = $this->memberAdminService->recordVisit(
                    $member,
                    $visitedAt,
                    MemberVisitSource::SMAREGI,
                    null,
                    'スマレジ取引 #'.$headId,
                    $amount,
                    $headId,
                );
                Log::info('[Smaregi] Inbound transaction sync recorded', [
                    'member_id' => (int) $member->id,
                    'project_id' => (int) $member->project_id,
                    'transaction_head_id' => $headId,
                    'amount' => $amount,
                    'visited_at' => $visitedAt->toIso8601String(),
                ]);
            } catch (UniqueConstraintViolationException $e) {
                $visit = MemberVisit::withoutGlobalScopes()
                    ->where('project_id', $member->project_id)
                    ->where('smaregi_transaction_head_id', $headId)
                    ->first();
                if ($visit === null) {
                    throw $e;
                }
            }
        }

        $this->awardStampsForPurchase($member, $amount, $headId);
        $this->awardPointsForPurchase($member, $amount, $headId, $transaction);

        if ($visit->items()->exists()) {
            return;
        }

        $this->saveVisitItems($visit, $transaction);
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function awardPointsForPurchase(Member $member, float $amount, string $headId, array $transaction): void
    {
        if ($amount <= 0) {
            return;
        }

        // スマレジ側で既に付与済みならローカル計算はせず、会員ポイント inbound に任せる
        if ($this->hasSmaregiManagedPointDelta($transaction)) {
            return;
        }

        $projectId = (int) $member->project_id;
        $rankKey = is_string($member->rank) ? $member->rank : null;
        $rate = $this->shopSettingRepository->getPointEarnRateForRank($projectId, $rankKey);
        if ($rate <= 0) {
            return;
        }

        $points = (int) floor($amount / $rate);
        if ($points <= 0) {
            return;
        }

        $description = $this->pointAwardDescription($headId);
        if ($this->hasPointAwardedForTransaction($member, $description)) {
            return;
        }

        $this->pointsSync->markOutboundPending((int) $member->id);

        $awarded = DB::transaction(function () use ($member, $projectId, $points, $description): int {
            $this->memberRepository->syncPointsLedger((int) $member->id, $projectId);
            $aggregate = $this->memberRepository->findByIdForUpdate((int) $member->id, $projectId);
            if ($aggregate === null) {
                return 0;
            }
            if ($this->hasPointAwardedForTransaction($member, $description)) {
                return 0;
            }
            $validDays = $this->shopSettingRepository->getPointsValidDays($projectId);
            $aggregate->earnPoints(
                $points,
                $description,
                null,
                now()->addDays($validDays),
            );
            $this->memberRepository->save($aggregate);

            return $points;
        });

        if ($awarded <= 0) {
            $this->pointsSync->clearOutboundPending((int) $member->id);

            return;
        }

        $this->domainEvents->dispatch(new PointsEarned(
            (int) $member->id,
            $projectId,
            $awarded,
            null,
            true,
        ));
        Log::info('[Smaregi] Inbound transaction sync points awarded', [
            'member_id' => (int) $member->id,
            'project_id' => $projectId,
            'transaction_head_id' => $headId,
            'points' => $awarded,
            'amount' => $amount,
            'rate' => $rate,
        ]);
    }

    /**
     * @param  string|int|float|bool|array|null  $value
     */
    private function parseOptionalInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }
        if (is_float($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * スマレジ取引の newPoint が増減している場合、ポイントはスマレジ側管理とみなす。
     *
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function hasSmaregiManagedPointDelta(array $transaction): bool
    {
        $newPoint = $this->parseOptionalInt($transaction['newPoint'] ?? null);

        return $newPoint !== null && $newPoint !== 0;
    }

    private function pointAwardDescription(string $headId): string
    {
        return 'スマレジ取引によるポイント付与 #'.$headId;
    }

    private function hasPointAwardedForTransaction(Member $member, string $description): bool
    {
        return PointTransaction::query()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->where('type', PointTransactionType::EARNED)
            ->where('description', $description)
            ->exists();
    }

    private function awardStampsForPurchase(Member $member, float $amount, string $headId): void
    {
        if ($amount <= 0) {
            return;
        }

        $projectId = (int) $member->project_id;
        $stampCardId = $this->shopSettingRepository->getOrderStampCardId($projectId);
        $stampEarnPerAmount = $this->shopSettingRepository->getStampEarnPerAmount($projectId);
        if ($stampCardId === null || $stampEarnPerAmount <= 0) {
            return;
        }

        $stampCount = $this->stampEarnPolicy->calculateCount($amount, $stampEarnPerAmount);
        if ($stampCount <= 0) {
            return;
        }

        $description = $this->stampAwardDescription($headId);
        if ($this->hasStampAwardedForTransaction($member, $description)) {
            return;
        }

        $this->memberAdminService->addStampsToMember(
            $member,
            $stampCardId,
            $stampCount,
            $description,
            null
        );
        Log::info('[Smaregi] Inbound transaction sync stamps awarded', [
            'member_id' => (int) $member->id,
            'project_id' => $projectId,
            'transaction_head_id' => $headId,
            'stamp_count' => $stampCount,
            'amount' => $amount,
        ]);
    }

    private function stampAwardDescription(string $headId): string
    {
        return 'スマレジ取引によるスタンプ付与 #'.$headId;
    }

    private function hasStampAwardedForTransaction(Member $member, string $description): bool
    {
        return MemberStampTransaction::query()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->where('description', $description)
            ->exists();
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function saveVisitItems(MemberVisit $visit, array $transaction): void
    {
        $details = $transaction['details'] ?? null;
        if (! is_array($details) || $details === []) {
            return;
        }

        $productIdBySmaregi = Product::query()
            ->where('project_id', $visit->project_id)
            ->whereNotNull('smaregi_product_id')
            ->pluck('id', 'smaregi_product_id')
            ->all();

        $sortOrder = 0;
        foreach ($details as $detail) {
            if (! is_array($detail)) {
                continue;
            }
            $row = $this->buildVisitItemRow($visit, $detail, $productIdBySmaregi, $sortOrder);
            if ($row === null) {
                continue;
            }
            MemberVisitItem::create($row);
            $sortOrder++;
        }
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $detail
     * @param  array<string, int|string>  $productIdBySmaregi
     * @return array<string, int|float|string|null>|null
     */
    private function buildVisitItemRow(
        MemberVisit $visit,
        array $detail,
        array $productIdBySmaregi,
        int $sortOrder,
    ): ?array {
        $productName = trim((string) ($detail['productName'] ?? $detail['printReceiptProductName'] ?? ''));
        if ($productName === '') {
            return null;
        }

        $smaregiProductId = trim((string) ($detail['productId'] ?? ''));
        $quantity = $this->parsePositiveInt($detail['quantity'] ?? 1) ?? 1;
        $unitPrice = $this->parseMoney($detail['salesPrice'] ?? $detail['price'] ?? 0) ?? 0.0;
        $lineAmount = $this->parseMoney($detail['unitDiscountedSum'] ?? $detail['unitNonDiscountSum'] ?? null);
        if ($lineAmount === null) {
            $lineAmount = $unitPrice * $quantity;
        }

        $localProductId = null;
        if ($smaregiProductId !== '' && isset($productIdBySmaregi[$smaregiProductId])) {
            $localProductId = (int) $productIdBySmaregi[$smaregiProductId];
        }

        return [
            'project_id' => (int) $visit->project_id,
            'member_visit_id' => (int) $visit->id,
            'product_id' => $localProductId,
            'smaregi_product_id' => $smaregiProductId !== '' ? $smaregiProductId : null,
            'product_name' => $productName,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'amount' => $lineAmount,
            'sort_order' => $sortOrder,
        ];
    }

    /**
     * @param  string|int|float|bool|array|null  $value
     */
    private function parsePositiveInt($value): ?int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }
        if (is_string($value) && $value !== '' && is_numeric($value)) {
            $parsed = (int) $value;

            return $parsed > 0 ? $parsed : null;
        }
        if (is_float($value) && $value > 0) {
            return (int) round($value);
        }

        return null;
    }

    /**
     * @param  string|int|float|bool|array|null  $value
     */
    private function parseMoney($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * @return list<int>
     */
    private function projectIdsForContract(string $contractId): array
    {
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

        $fromEffectiveContract = [];
        foreach (Project::query()->pluck('id') as $projectId) {
            $effective = $this->smaregiApi->getContractIdForProject((int) $projectId);
            if (is_string($effective) && trim($effective) === $contractId) {
                $fromEffectiveContract[] = (int) $projectId;
            }
        }

        return array_values(array_unique($fromEffectiveContract));
    }
}
