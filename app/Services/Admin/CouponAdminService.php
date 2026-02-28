<?php

namespace App\Services\Admin;

use App\Models\Coupon;
use App\Models\StampCard;
use App\Services\Smaregi\SmaregiCouponSyncService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CouponAdminService
{
    public function __construct(
        private SmaregiCouponSyncService $smaregiCouponSync
    ) {
    }
    private const COUPON_IMAGE_DIR = 'coupon_images';

    /** アップロードした画像を保存し、公開URLを返す */
    public function saveCouponImage(UploadedFile $file, int $projectId): string
    {
        $dir = self::COUPON_IMAGE_DIR . '/' . (string) $projectId;
        $path = $file->store($dir, 'public');
        if ($path === false) {
            throw new \RuntimeException('Failed to store coupon image');
        }
        return asset('storage/' . $path);
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
     * クーポン作成。スマレジ契約ありの場合はPOSへ登録を試み、失敗時はDBのみ保存して警告を返す。
     *
     * @return array{coupon: Coupon, smaregi_warning: bool} smaregi_warning=true は「POS未反映」の警告あり
     */
    public function create(int $projectId, array $data): array
    {
        $data['project_id'] = $projectId;
        $smaregiWarning = false;
        if ($this->smaregiCouponSync->hasSmaregiContract($projectId)) {
            $result = $this->smaregiCouponSync->registerToSmaregi($projectId, $data);
            if ($result !== null) {
                $data['smaregi_coupon_id'] = $result['smaregi_coupon_id'];
            } else {
                $smaregiWarning = true;
            }
        }
        $coupon = Coupon::create($data);
        return ['coupon' => $coupon, 'smaregi_warning' => $smaregiWarning];
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
     * クーポン更新。スマレジ登録・更新に失敗した場合はDBのみ更新し、警告を返す。
     *
     * @return array{coupon: Coupon, smaregi_warning: bool}
     */
    public function update(int $id, array $data, ?int $projectId = null): array
    {
        $coupon = $this->findOrFail($id, $projectId);
        $projectId = $projectId ?? $coupon->project_id;
        $smaregiWarning = false;
        if ($this->smaregiCouponSync->hasSmaregiContract($projectId)) {
            $smaregiId = $coupon->smaregi_coupon_id;
            if ($smaregiId !== null && $smaregiId !== '') {
                $ok = $this->smaregiCouponSync->updateOnSmaregi($projectId, $smaregiId, $data);
                if (!$ok) {
                    $smaregiWarning = true;
                }
            } else {
                $result = $this->smaregiCouponSync->registerToSmaregi($projectId, $data);
                if ($result !== null) {
                    $data['smaregi_coupon_id'] = $result['smaregi_coupon_id'];
                } else {
                    $smaregiWarning = true;
                }
            }
        }
        $coupon->update($data);
        return ['coupon' => $coupon, 'smaregi_warning' => $smaregiWarning];
    }

    public function delete(int $id, ?int $projectId = null): void
    {
        $coupon = $this->findOrFail($id, $projectId);
        $coupon->delete();
    }

}
