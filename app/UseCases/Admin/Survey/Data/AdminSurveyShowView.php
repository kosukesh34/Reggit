<?php

namespace App\UseCases\Admin\Survey\Data;

use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Collection;

/** アンケート結果画面。 */
final class AdminSurveyShowView
{
    /**
     * @param  Collection<int, SurveyResponse>  $responses
     * @param  list<array<string, mixed>>  $stats  質問ごとの集計
     * @param  array<string, int>  $npsTagIds  NPS区分のタグ名 => タグID（「この区分の会員へ配信」に使う）
     */
    public function __construct(
        public readonly Survey $survey,
        public readonly bool $isOpen,
        public readonly Collection $responses,
        public readonly array $stats,
        public readonly int $invitedCount,
        public readonly int $invitedAnsweredCount,
        public readonly string $shareUrl,
        public readonly array $npsTagIds,
    ) {}

    /** 自動の回答依頼に対する回答率（%）。依頼を送っていなければ NULL */
    public function answerRate(): ?int
    {
        return $this->invitedCount === 0 ? null : (int) round($this->invitedAnsweredCount * 100 / $this->invitedCount);
    }
}
