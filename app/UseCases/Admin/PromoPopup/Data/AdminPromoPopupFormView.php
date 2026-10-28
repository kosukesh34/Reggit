<?php

namespace App\UseCases\Admin\PromoPopup\Data;

use App\Models\PromoPopup;

/** ポップアップの作成・編集画面。表示相手・店舗の選択肢を含む。 */
final class AdminPromoPopupFormView
{
    /**
     * @param  array<int, string>  $tags  タグID => タグ名
     * @param  array<string, string>  $ranks  ランクのキー => 表示名
     * @param  array<int, string>  $stores  店舗ID => 店舗名（1店舗だけなら空）
     * @param  array<string, string>  $linkOptions  リンク先の候補（アプリ内パス => 名前）
     */
    public function __construct(
        public readonly ?PromoPopup $popup,
        public readonly array $tags,
        public readonly array $ranks,
        public readonly array $stores,
        public readonly array $linkOptions,
    ) {}
}
