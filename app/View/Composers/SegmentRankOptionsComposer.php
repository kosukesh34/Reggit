<?php

namespace App\View\Composers;

use App\Domain\Settings\Repositories\MemberCardPreviewSettings;
use App\Util\AdminContext;
use Illuminate\View\View;

/** 配信・会員一覧の絞り込み「会員ランク」で選べるランク（キー => 表示名） */
final class SegmentRankOptionsComposer
{
    public function __construct(private readonly MemberCardPreviewSettings $settings) {}

    public function compose(View $view): void
    {
        $projectId = AdminContext::getCurrentProjectId();
        $options = [];
        foreach ($projectId === null ? [] : $this->settings->getMemberRankSettings($projectId) as $rank) {
            $key = trim((string) ($rank['key'] ?? ''));
            if ($key !== '') {
                $options[$key] = (string) ($rank['label'] ?? $key);
            }
        }
        $view->with('segmentRankOptions', $options);
    }
}
