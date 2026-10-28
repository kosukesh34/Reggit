<?php

namespace App\UseCases\Admin\Survey\Data;

use App\Models\Survey;

/** アンケートの作成・編集画面。回答が入ったら質問は変更できない（locked）。 */
final class AdminSurveyFormView
{
    /**
     * @param  Survey|null  $survey  編集するアンケート。作成のときは NULL
     * @param  Survey|null  $draft  作成画面に入れておく内容（雛形から作るとき）。保存はしていない
     * @param  array<string, array{label: string, description: string, count: int, icon: string}>  $templates  雛形のキー => 選ぶカードの内容
     * @param  array<int, string>  $tags  選択肢に付けられるタグ（タグID => タグ名）
     */
    public function __construct(
        public readonly ?Survey $survey,
        public readonly bool $locked,
        public readonly ?Survey $draft,
        public readonly array $templates,
        public readonly array $tags,
    ) {}

    /** 入力欄の初期値に使うアンケート */
    public function values(): ?Survey
    {
        return $this->survey ?? $this->draft;
    }
}
