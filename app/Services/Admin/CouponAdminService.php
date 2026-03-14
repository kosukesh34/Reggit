<?php

namespace App\Services\Admin;

use App\Models\Coupon;
use App\Models\StampCard;
use App\Services\Smaregi\SmaregiCouponSyncService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class CouponAdminService
{
    /** @var SmaregiCouponSyncService */
    private $smaregiCouponSync;

    public function __construct(SmaregiCouponSyncService $smaregiCouponSync)
    {
        $this->smaregiCouponSync = $smaregiCouponSync;
    }

    private const COUPON_IMAGE_DIR = 'coupon_images';

    /** アップロードした画像を保存し、公開URLを返す */
    public function saveCouponImage(UploadedFile $file, int $projectId): string
    {
        $dir = self::COUPON_IMAGE_DIR.'/'.(string) $projectId;
        $path = $file->store($dir, 'public');
        if ($path === false) {
            throw new \RuntimeException('Failed to store coupon image');
        }

        return asset('storage/'.$path);
    }

    public function getPaginatedList(int $projectId, int $perPage = 20): LengthAwarePaginator
    {
        return Coupon::where('project_id', $projectId)
            ->with('stampCard')
            ->orderByDesc('id')
            ->paginate($perPage);
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
        if ($this->smaregiCouponSync->hasSmaregiContract($projectId)) {
            $result = $this->smaregiCouponSync->registerToSmaregi($projectId, $data);
            if ($result === null) {
                throw new \RuntimeException('スマレジへのクーポン登録に失敗しました。契約ID・スコープ(pos.transactions:write)・ログ(storage/logs/laravel.log)を確認してください。');
            }
            $data['smaregi_coupon_id'] = $result['smaregi_coupon_id'];
        }

        return Coupon::create($data);
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
     * クーポン更新。スマレジ契約ありの場合は先にPOSへ更新/登録し、成功時のみDBを更新する。
     * API が失敗した場合は RuntimeException を投げ、DB は更新しない。
     */
    public function update(int $id, array $data, ?int $projectId = null): Coupon
    {
        $coupon = $this->findOrFail($id, $projectId);
        $projectId = $projectId ?? $coupon->project_id;
        if ($this->smaregiCouponSync->hasSmaregiContract($projectId)) {
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
            }
        }
        $coupon->update($data);

        return $coupon;
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
