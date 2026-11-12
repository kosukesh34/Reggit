<?php

namespace App\Support;

class ShopSettingLiffDefaults
{
    public static function defaultLiffTabSettings(): array
    {
        $theme = LiffAppStudioTemplates::theme([
            'content_padding' => 18,
            'section_gap' => 20,
            'require_membership' => true,
        ]);
        $settings = ['_global' => $theme];
        foreach (LiffAppStudioTemplates::emptyTabs() as $tab => $bg) {
            $settings[$tab] = $bg;
        }
        foreach ([
            'announcement_detail', 'product_detail', 'coupon_detail', 'gallery_detail', 'stamp_detail',
            'order_cart', 'order_confirm', 'order_complete',
            'reservation_new', 'reservation_confirm', 'reservation_complete',
        ] as $detailTab) {
            $settings[$detailTab] = ['background_color' => '', 'background_image' => ''];
        }

        return $settings;
    }
}
