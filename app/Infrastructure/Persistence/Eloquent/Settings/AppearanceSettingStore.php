<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

use App\Util\AdminContext;

/** 管理画面・フロント・Wallet の色と画像の設定。 */
class AppearanceSettingStore
{
    public function __construct(
        private ShopSettingStore $values,
    ) {}

    public function getAdminSidebarColor(?int $projectId = null): ?string
    {
        return $this->values->getValue('admin_sidebar_color', null, $projectId);
    }

    public function getAdminHeaderColor(?int $projectId = null): ?string
    {
        return $this->values->getValue('admin_header_color', null, $projectId);
    }

    public function getStampDefaultImageUrl(?int $projectId = null): ?string
    {
        return $this->values->getValue('stamp_default_image_url', null, $projectId);
    }

    public function getCouponDefaultImageUrl(?int $projectId = null): ?string
    {
        return $this->values->getValue('coupon_default_image_url', null, $projectId);
    }

    public function getAdminPrimaryColor(?int $projectId = null): ?string
    {
        return $this->values->getValue('admin_primary_color', null, $projectId);
    }

    public function getFrontendLogoUrl(?int $projectId = null): ?string
    {
        return $this->values->getValue('frontend_logo_url', null, $projectId);
    }

    /** Google Wallet プログラムロゴ画像URL（管理画面で設定、未設定時は config を使用） */
    public function getWalletGoogleProgramLogoUrl(?int $projectId = null): ?string
    {
        $v = $this->values->getValue('wallet_google_program_logo_url', null, $projectId);

        return is_string($v) && $v !== '' ? trim($v) : null;
    }

    /** Google Wallet ヒーロー画像URL（管理画面で設定、未設定時は config を使用） */
    public function getWalletGoogleHeroImageUrl(?int $projectId = null): ?string
    {
        $v = $this->values->getValue('wallet_google_hero_image_url', null, $projectId);

        return is_string($v) && $v !== '' ? trim($v) : null;
    }

    public function setWalletGoogleProgramLogoUrl(?string $url, ?int $projectId = null): void
    {
        $this->values->setValue('wallet_google_program_logo_url', trim((string) ($url ?? '')), 'string', AdminContext::resolveProjectId($projectId));
    }

    public function setWalletGoogleHeroImageUrl(?string $url, ?int $projectId = null): void
    {
        $this->values->setValue('wallet_google_hero_image_url', trim((string) ($url ?? '')), 'string', AdminContext::resolveProjectId($projectId));
    }

    public function getFrontendHeaderText(?int $projectId = null): ?string
    {
        return $this->values->getValue('frontend_header_text', null, $projectId);
    }
}
