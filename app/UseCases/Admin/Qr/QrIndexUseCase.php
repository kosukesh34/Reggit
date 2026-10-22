<?php

namespace App\UseCases\Admin\Qr;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Models\PointGrantCode;
use App\Models\Project;
use App\Models\StampCard;
use App\Models\StampGrantCode;
use App\Repositories\Eloquent\ProjectAppLinkRepository;
use App\Support\ProjectQrAppUrl;
use App\UseCases\Admin\Qr\Data\QrIndexOutput;

class QrIndexUseCase
{
    public function __construct(
        private readonly ProjectAppLinkRepository $appLinks,
        private readonly FeatureSettings $features,
    ) {}

    public function handle(int $projectId, ?int $issuedPointCodeId = null, ?int $issuedStampCodeId = null): QrIndexOutput
    {
        $native = $this->appLinks->find($projectId);
        $liff = ProjectQrAppUrl::lineUrl($projectId);
        $project = Project::query()->findOrFail($projectId);
        $orderUrl = ProjectQrAppUrl::tabUrl($project, 'products');
        $links = [
            'line' => $this->link('LINEミニアプリ', $liff, 'LINEからアプリを開くためのQRです。'),
            'mobile-order' => $this->link('モバイル注文', $orderUrl, '商品を選んで注文する画面を直接開きます。'),
            'native' => $this->link('ネイティブアプリ', (string) $native?->download_url, 'アプリのダウンロード案内ページを開きます。'),
        ];
        if ($this->features->isFeatureEnabled('feature_shop_enabled', $projectId)) {
            $links['shop'] = $this->link('オンラインショップ', $this->shopUrl($project), '配送ECの公開ページを開きます。');
        }

        return new QrIndexOutput($links, StampCard::query()->where('project_id', $projectId)->where('is_active', true)->orderBy('id')->get(), $issuedPointCodeId === null ? null : PointGrantCode::query()->where('project_id', $projectId)->where('id', $issuedPointCodeId)->first(), $issuedStampCodeId === null ? null : StampGrantCode::query()->where('project_id', $projectId)->where('id', $issuedStampCodeId)->first());
    }

    private function shopUrl(Project $project): string
    {
        $domain = is_string($project->custom_shop_domain) ? trim($project->custom_shop_domain) : '';

        return $domain !== ''
            ? 'https://'.$domain.'/shop'
            : route('shop.show', ['project_key' => $project->project_key]);
    }

    /** @return array{title: string, url: string, description: string} */
    private function link(string $title, string $url, string $description): array
    {
        $url = trim($url);
        $isSafe = filter_var($url, FILTER_VALIDATE_URL) !== false && in_array(parse_url($url, PHP_URL_SCHEME), ['https', 'http'], true);

        return ['title' => $title, 'url' => $isSafe ? $url : '', 'description' => $description];
    }
}
