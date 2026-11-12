<?php

namespace App\Services\Admin;

use App\Domain\Settings\Repositories\LiffDesignerSettings;
use App\Support\MemberCardDesignMap;

/**
 * LIFF デザイナー・フロントメニュー設定の永続化と取得を担当する。
 */
class LiffSettingsService
{
    /** @var LiffDesignerSettings */
    private $shopSettingRepository;

    public function __construct(LiffDesignerSettings $shopSettingRepository)
    {
        $this->shopSettingRepository = $shopSettingRepository;
    }

    /**
     * @return array<string, string|int|float|bool|array|null>
     */
    public function getTabSettings(int $projectId): array
    {
        return $this->shopSettingRepository->getLiffTabSettings($projectId);
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $settings
     */
    public function setTabSettings(array $settings, int $projectId): void
    {
        $this->shopSettingRepository->setLiffTabSettings($settings, $projectId);
    }

    /**
     * ランク設定の member_card_design を app_customization.membership.template へ同期する。
     */
    public function syncMembershipTemplateFromDesign(string $design, int $projectId): void
    {
        if (MemberCardDesignMap::templateForDesign($design) === null) {
            return;
        }

        $settings = $this->getTabSettings($projectId);
        $global = isset($settings['_global']) && is_array($settings['_global']) ? $settings['_global'] : [];
        $global = MemberCardDesignMap::withTemplate($global, $design);
        $global['member_card_design'] = $design;
        $settings['_global'] = $global;
        $this->setTabSettings($settings, $projectId);
    }
}
