<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

use App\Util\AdminContext;

/** 管理画面・フロント・Wallet の色と画像の設定。 */
class AppearanceSettingStore
{
    public static function getAdminSidebarColor(?int $projectId = null): ?string
    {
        return ShopSettingStore::getValue('admin_sidebar_color', null, $projectId);
    }

    public static function getAdminHeaderColor(?int $projectId = null): ?string
    {
        return ShopSettingStore::getValue('admin_header_color', null, $projectId);
    }

    public static function getStampDefaultImageUrl(?int $projectId = null): ?string
    {
        return ShopSettingStore::getValue('stamp_default_image_url', null, $projectId);
    }

    public static function getCouponDefaultImageUrl(?int $projectId = null): ?string
    {
        return ShopSettingStore::getValue('coupon_default_image_url', null, $projectId);
    }

    public static function getAdminPrimaryColor(?int $projectId = null): ?string
    {
        return ShopSettingStore::getValue('admin_primary_color', null, $projectId);
    }

    public static function getFrontendLogoUrl(?int $projectId = null): ?string
    {
        return ShopSettingStore::getValue('frontend_logo_url', null, $projectId);
    }

    /** Google Wallet プログラムロゴ画像URL（管理画面で設定、未設定時は config を使用） */
    public static function getWalletGoogleProgramLogoUrl(?int $projectId = null): ?string
    {
        $v = ShopSettingStore::getValue('wallet_google_program_logo_url', null, $projectId);

        return is_string($v) && $v !== '' ? trim($v) : null;
    }

    /** Google Wallet ヒーロー画像URL（管理画面で設定、未設定時は config を使用） */
    public static function getWalletGoogleHeroImageUrl(?int $projectId = null): ?string
    {
        $v = ShopSettingStore::getValue('wallet_google_hero_image_url', null, $projectId);

        return is_string($v) && $v !== '' ? trim($v) : null;
    }

    public static function setWalletGoogleProgramLogoUrl(?string $url, ?int $projectId = null): void
    {
        ShopSettingStore::setValue('wallet_google_program_logo_url', trim((string) ($url ?? '')), 'string', AdminContext::resolveProjectId($projectId));
    }

    public static function setWalletGoogleHeroImageUrl(?string $url, ?int $projectId = null): void
    {
        ShopSettingStore::setValue('wallet_google_hero_image_url', trim((string) ($url ?? '')), 'string', AdminContext::resolveProjectId($projectId));
    }

    public static function getFrontendHeaderText(?int $projectId = null): ?string
    {
        return ShopSettingStore::getValue('frontend_header_text', null, $projectId);
    }
}
