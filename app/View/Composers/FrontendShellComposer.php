<?php

namespace App\View\Composers;

use App\Domain\Settings\Repositories\FrontendShellSettings;
use App\Domain\Settings\ShopSettingKeys;
use App\Enums\MemberRegistrationMode;
use App\Services\GoogleWalletPassService;
use App\Services\WalletPassService;
use App\Util\AdminContext;
use Illuminate\View\View;

final class FrontendShellComposer
{
    public function __construct(
        private readonly FrontendShellSettings $settings,
        private readonly WalletPassService $walletPass,
        private readonly GoogleWalletPassService $googleWalletPass,
        private readonly DesignerPreviewMemberPayloadBuilder $previewBuilder,
    ) {}

    public function compose(View $view): void
    {
        try {
            $request = request();
            $project = $request->attributes->get('currentProject') ?? AdminContext::getCurrentProject();
            $projectId = $project ? (int) $project->id : 0;

            $view->with([
                'lineTheme' => $this->settings->getLineThemeColors($projectId),
                'frontendMenu' => $this->settings->getFrontendMenuItems($projectId),
                'projectKey' => $project ? (string) $project->project_key : '',
                'projectName' => $project ? (string) $project->name : config('app.name'),
                'featureFlags' => $this->settings->getFeatureFlags($projectId),
                'walletPassEnabled' => $this->walletPass->isAvailable(),
                'googleWalletEnabled' => $this->googleWalletPass->isAvailable(),
                'frontendLogoUrl' => $this->settings->getFrontendLogoUrl($projectId),
                'frontendHeaderText' => $this->settings->getFrontendHeaderText($projectId),
                'orderMode' => $this->settings->getOrderMode($projectId),
                'liffTabLayouts' => $this->settings->getLiffTabLayouts($projectId),
                'liffDetailLayouts' => $this->settings->getLiffDetailLayouts($projectId),
                'liffCustomCss' => $this->settings->getLiffCustomCss($projectId),
                'liffTabSettings' => $this->settings->getLiffTabSettings($projectId),
                'liffId' => $this->settings->getLineLiffId($projectId),
                'liffUrl' => $this->settings->getLineLiffUrl($projectId),
                'lineFriendAddUrl' => $this->settings->getLineFriendAddUrl($projectId),
                'queueRequireMembership' => $this->settings->getQueueRequireMembership($projectId),
                'memberRegistrationMode' => $this->settings->getMemberRegistrationMode($projectId),
                'designerPreviewMemberPayload' => $this->previewPayload($projectId),
            ]);
        } catch (\Exception $exception) {
            report($exception);
            $this->applyFallbacks($view);
        }
    }

    private function previewPayload(int $projectId): ?array
    {
        $request = request();
        $source = $request->query('designer_source');

        if ($request->query('designer_live_preview') !== '1'
            || $request->query('designer_embed') !== '1'
            || ! in_array($source, ['member', 'member_card'], true)) {
            return null;
        }

        return $this->previewBuilder->build($projectId);
    }

    private function applyFallbacks(View $view): void
    {
        $view->with([
            'lineTheme' => [
                'primary' => '#4f46e5',
                'primary_rgb' => '79, 70, 229',
                'primary_dark' => '#4338ca',
                'success' => '#059669',
                'danger' => '#dc2626',
            ],
            'frontendMenu' => $this->settings->defaultFrontendMenuItems(),
            'projectKey' => '',
            'projectName' => config('app.name'),
            'featureFlags' => [],
            'walletPassEnabled' => false,
            'googleWalletEnabled' => false,
            'frontendLogoUrl' => '',
            'frontendHeaderText' => config('app.name'),
            'orderMode' => ShopSettingKeys::ORDER_MODE_BOTH,
            'liffTabLayouts' => $this->settings->defaultLiffTabLayouts(),
            'liffDetailLayouts' => $this->settings->defaultLiffDetailLayouts(),
            'liffCustomCss' => '',
            'liffTabSettings' => [],
            'liffId' => trim((string) config('line.liff_id', '')),
            'liffUrl' => trim((string) config('line.liff_url', '')),
            'lineFriendAddUrl' => '',
            'queueRequireMembership' => false,
            'memberRegistrationMode' => MemberRegistrationMode::OPTIONAL,
            'designerPreviewMemberPayload' => null,
        ]);
    }
}
