<?php

namespace App\Services\Admin;

use App\Models\MemberTag;
use App\Models\PromoPopup;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

/** 管理画面の宣伝ポップアップの保存・読み取り。 */
class PromoPopupAdminService
{
    public function __construct(private readonly PublicImageStorageService $images) {}

    /** @return Collection<int, PromoPopup> 優先度の高い順。表示・タップの合計（views_total など）付き */
    public function list(int $projectId): Collection
    {
        return PromoPopup::query()->where('project_id', $projectId)
            ->withSum('stats as views_total', 'views')
            ->withSum('stats as clicks_total', 'clicks')
            ->withSum('stats as secondary_clicks_total', 'secondary_clicks')
            ->orderByDesc('sort_order')->latest('id')->get();
    }

    /** @return array<int, string> */
    public function tagNames(int $projectId): array
    {
        return MemberTag::query()->where('project_id', $projectId)->orderBy('name')->pluck('name', 'id')->all();
    }

    /** @return array<int, string> 有効な店舗 */
    public function storeNames(int $projectId): array
    {
        return Store::query()->where('project_id', $projectId)->where('is_active', true)->orderBy('id')->pluck('name', 'id')->all();
    }

    /** 内容をそのまま写したポップアップを、表示を止めた状態で作る */
    public function duplicate(PromoPopup $popup): PromoPopup
    {
        $copy = $popup->replicate();
        $copy->fill(['title' => mb_substr($popup->title.'（コピー）', 0, 120), 'is_active' => false])->save();

        return $copy;
    }

    public function find(int $projectId, int $popupId): PromoPopup
    {
        return PromoPopup::query()->where('project_id', $projectId)->findOrFail($popupId);
    }

    /** @param array<string, mixed> $attributes 画像以外の項目 */
    public function create(int $projectId, array $attributes, ?UploadedFile $image, bool $removeImage): PromoPopup
    {
        return PromoPopup::create(['project_id' => $projectId, ...$this->withImage($projectId, $attributes, $image, $removeImage)]);
    }

    /** @param array<string, mixed> $attributes 画像以外の項目 */
    public function update(PromoPopup $popup, array $attributes, ?UploadedFile $image, bool $removeImage): void
    {
        $popup->update($this->withImage((int) $popup->project_id, $attributes, $image, $removeImage));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withImage(int $projectId, array $attributes, ?UploadedFile $image, bool $removeImage): array
    {
        if ($image !== null) {
            $attributes['image_url'] = $this->images->store($image, $projectId, PublicImageStorageService::DIR_PROMO_POPUP);
        } elseif ($removeImage) {
            $attributes['image_url'] = null;
        }

        return $attributes;
    }
}
