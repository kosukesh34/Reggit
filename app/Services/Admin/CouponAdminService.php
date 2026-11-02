<?php

namespace App\Services\Admin;

use App\Enums\CouponApplyConditionDivision;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Product;
use App\Models\StampCard;
use App\Services\Smaregi\SmaregiCouponSyncService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CouponAdminService
{
    /** @var SmaregiCouponSyncService */
    private $smaregiCouponSync;

    public function __construct(SmaregiCouponSyncService $smaregiCouponSync)
    {
        $this->smaregiCouponSync = $smaregiCouponSync;
    }

    public function getPaginatedList(int $projectId, int $perPage = 20): LengthAwarePaginator
    {
        return Coupon::where('project_id', $projectId)
            ->with('stampCard')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'coupons_page');
    }

    public function getActiveStampCardsForSelect(int $projectId): Collection
    {
        return StampCard::where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * クーポン作成。スマレジ契約ありの場合は先にPOSへ登録し、成功時のみDBに保存する。
     * API が失敗した場合は RuntimeException を投げ、DB には保存しない。
     */
    public function create(int $projectId, array $data): Coupon
    {
        $data['project_id'] = $projectId;
        $productIds = $this->pullTargetProductIds($data);
        if ($this->smaregiCouponSync->hasSmaregiContract($projectId)) {
            $smaregiProductIds = $this->smaregiProductIds($projectId, $productIds);
            $result = $this->smaregiCouponSync->registerToSmaregi($projectId, $data);
            if ($result === null) {
                throw new \RuntimeException('スマレジへのクーポン登録に失敗しました。契約ID・スコープ(pos.transactions:write)・ログ(storage/logs/laravel.log)を確認してください。');
            }
            $data['smaregi_coupon_id'] = $result['smaregi_coupon_id'];
            if ($smaregiProductIds !== [] && ! $this->smaregiCouponSync->syncTargetProducts($projectId, $result['smaregi_coupon_id'], $smaregiProductIds)) {
                // 対象商品なしのクーポンをPOSに残さない
                $this->smaregiCouponSync->deleteOnSmaregi($projectId, $result['smaregi_coupon_id']);
                throw new \RuntimeException('スマレジへの対象商品の登録に失敗しました。ログ(storage/logs/laravel.log)を確認してください。');
            }
        }

        return DB::transaction(function () use ($data, $productIds): Coupon {
            $coupon = Coupon::create($data);
            $coupon->targetProducts()->sync($productIds);

            return $coupon;
        });
    }

    /** @return Collection<int, Product> */
    public function getProductsForSelect(int $projectId): Collection
    {
        return Product::where('project_id', $projectId)->orderBy('name')->get(['id', 'name', 'price', 'smaregi_product_id']);
    }

    public function existsForProject(int $projectId, int $couponId): bool
    {
        return Coupon::where('project_id', $projectId)->whereKey($couponId)->exists();
    }

    /** @param int|null $projectId 指定時はそのプロジェクトのクーポンのみ取得（他プロジェクトは404） */
    public function findOrFail(int $id, ?int $projectId = null): Coupon
    {
        $query = Coupon::with('stampCard');
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        return $query->findOrFail($id);
    }

    /**
     * @return LengthAwarePaginator<int, array{member_id: int|null, member_name: string, member_number: string, used_at: string}>
     */
    public function getRedemptionsForCoupon(int $couponId, int $projectId, int $perPage = 20): LengthAwarePaginator
    {
        $paginator = CouponRedemption::query()
            ->where('project_id', $projectId)
            ->where('coupon_id', $couponId)
            ->with(['member.user'])
            ->orderByDesc('used_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $rows = $paginator->getCollection()->map(function (CouponRedemption $redemption): array {
            $member = $redemption->member;
            $user = $member?->user;

            return [
                'member_id' => $member !== null ? (int) $member->id : null,
                'member_name' => $user !== null && trim((string) $user->name) !== ''
                    ? (string) $user->name
                    : '—',
                'member_number' => $member !== null && trim((string) $member->member_number) !== ''
                    ? (string) $member->member_number
                    : '—',
                'used_at' => $redemption->used_at !== null
                    ? $redemption->used_at->format('Y/m/d H:i')
                    : '—',
            ];
        });

        $paginator->setCollection($rows);

        return $paginator;
    }

    /**
     * 配布画面用の有効クーポン
     *
     * @return Collection<int, Coupon>
     */
    public function getActiveCouponsForSelect(int $projectId): Collection
    {
        return Coupon::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * クーポン更新。スマレジ契約ありの場合は先にPOSへ更新/登録し、成功時のみDBを更新する。
     * API が失敗した場合は RuntimeException を投げ、DB は更新しない。
     */
    public function update(int $id, array $data, ?int $projectId = null): Coupon
    {
        $coupon = $this->findOrFail($id, $projectId);
        $projectId = $projectId ?? $coupon->project_id;
        $productIds = $this->pullTargetProductIds($data);
        if ($this->smaregiCouponSync->hasSmaregiContract($projectId)) {
            $smaregiProductIds = $this->smaregiProductIds($projectId, $productIds);
            $smaregiId = $coupon->smaregi_coupon_id;
            if ($smaregiId !== null && $smaregiId !== '') {
                $ok = $this->smaregiCouponSync->updateOnSmaregi($projectId, $smaregiId, $data);
                if (! $ok) {
                    throw new \RuntimeException('スマレジのクーポン更新に失敗しました。ログ(storage/logs/laravel.log)を確認してください。');
                }
            } else {
                $result = $this->smaregiCouponSync->registerToSmaregi($projectId, $data);
                if ($result === null) {
                    throw new \RuntimeException('スマレジへのクーポン登録に失敗しました。契約ID・スコープ(pos.transactions:write)・ログ(storage/logs/laravel.log)を確認してください。');
                }
                $data['smaregi_coupon_id'] = $result['smaregi_coupon_id'];
                $smaregiId = $result['smaregi_coupon_id'];
            }
            if (! $this->smaregiCouponSync->syncTargetProducts($projectId, $smaregiId, $smaregiProductIds)) {
                throw new \RuntimeException('スマレジの対象商品の更新に失敗しました。ログ(storage/logs/laravel.log)を確認してください。');
            }
        }
        DB::transaction(function () use ($coupon, $data, $productIds): void {
            $coupon->update($data);
            $coupon->targetProducts()->sync($productIds);
        });

        return $coupon;
    }

    /**
     * 対象商品IDをフォームデータから取り出す。全適用なら対象商品は持たない。
     *
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    private function pullTargetProductIds(array &$data): array
    {
        $ids = $data['target_product_ids'] ?? [];
        unset($data['target_product_ids']);
        if (($data['apply_condition_division'] ?? null) !== CouponApplyConditionDivision::INCLUDE_TARGET_PRODUCT) {
            return [];
        }

        return array_values(array_map('intval', (array) $ids));
    }

    /**
     * @param  list<int>  $productIds
     * @return list<string>
     */
    private function smaregiProductIds(int $projectId, array $productIds): array
    {
        $products = Product::where('project_id', $projectId)->whereIn('id', $productIds)->get(['name', 'smaregi_product_id']);
        $unlinked = $products->filter(fn (Product $product) => (string) $product->smaregi_product_id === '');
        if ($unlinked->isNotEmpty()) {
            throw new \RuntimeException('スマレジ未連携の商品は対象商品にできません：'.$unlinked->pluck('name')->implode('、'));
        }

        return $products->pluck('smaregi_product_id')->map(fn ($id) => (string) $id)->values()->all();
    }

    /**
     * クーポン削除。スマレジ契約ありでスマレジ連携済みの場合は先にPOSから削除し、成功時のみDBから削除する。
     */
    public function delete(int $id, ?int $projectId = null): void
    {
        $coupon = $this->findOrFail($id, $projectId);
        $projectId = $projectId ?? $coupon->project_id;
        $smaregiId = $coupon->smaregi_coupon_id ?? '';

        if ($smaregiId !== null && $smaregiId !== '' && $this->smaregiCouponSync->hasSmaregiContract($projectId)) {
            $ok = $this->smaregiCouponSync->deleteOnSmaregi($projectId, $smaregiId);
            if (! $ok) {
                throw new \RuntimeException('スマレジのクーポン削除に失敗しました。ログ(storage/logs/laravel.log)を確認してください。');
            }
        }

        $coupon->delete();
    }
}
