<?php

namespace App\UseCases\Survey\Data;

use App\Domain\Survey\ValueObjects\SurveyDefinition;

/** 会員アプリへ返すアンケート。 */
final class SurveyView
{
    public function __construct(
        private readonly SurveyDefinition $survey,
        private readonly bool $isOpen,
        private readonly bool $answered,
        /** 実際に付与するポイント（ポイント機能が無効なら 0） */
        private readonly int $rewardPoints,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->survey->id,
            'title' => $this->survey->title,
            'description' => $this->survey->description,
            // 選択肢に付けるタグは店舗側の分類なので会員には返さない
            'questions' => array_map(fn (array $question) => array_diff_key($question, ['option_tags' => true]), $this->survey->questions),
            'reward_points' => $this->rewardPoints,
            'is_open' => $this->isOpen,
            'answered' => $this->answered,
        ];
    }
}
