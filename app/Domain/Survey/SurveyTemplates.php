<?php

namespace App\Domain\Survey;

/**
 * アンケートを一から作らずに始めるための雛形。選ぶと作成画面に質問が入った状態で開く。
 *
 * @phpstan-import-type Question from \App\Domain\Survey\Policies\SurveyAnswerPolicy
 */
final class SurveyTemplates
{
    private const NPS = ['type' => SurveyQuestionType::NPS, 'label' => 'このお店を友人や知人にすすめる可能性はどのくらいありますか？', 'required' => true];

    /** @var array<string, array{label: string, title: string, description: string, questions: list<Question>}> */
    private const TEMPLATES = [
        'visit' => [
            'label' => '来店満足度',
            'title' => 'ご来店アンケート',
            'description' => 'ご来店ありがとうございました。より良いお店づくりのため、ご意見をお聞かせください。',
            'questions' => [
                self::NPS,
                ['type' => SurveyQuestionType::RATING, 'label' => '接客の満足度', 'required' => true],
                ['type' => SurveyQuestionType::RATING, 'label' => '店内の雰囲気・清潔さ', 'required' => false],
                ['type' => SurveyQuestionType::CHOICE, 'label' => 'ご来店の目的', 'options' => ['食事', '買い物', 'テイクアウト', 'その他'], 'required' => false],
                ['type' => SurveyQuestionType::TEXT, 'label' => 'ご意見・ご感想', 'required' => false],
            ],
        ],
        'nps' => [
            'label' => 'NPSだけ（1問）',
            'title' => 'おすすめ度アンケート',
            'description' => '1問だけのアンケートです。ご協力をお願いします。',
            'questions' => [self::NPS, ['type' => SurveyQuestionType::TEXT, 'label' => 'そう思われた理由', 'required' => false]],
        ],
        'product' => [
            'label' => '商品・メニュー評価',
            'title' => '商品についてのアンケート',
            'description' => 'お召し上がりいただいた商品について教えてください。',
            'questions' => [
                ['type' => SurveyQuestionType::RATING, 'label' => '味', 'required' => true],
                ['type' => SurveyQuestionType::RATING, 'label' => '量', 'required' => false],
                ['type' => SurveyQuestionType::RATING, 'label' => '価格の満足度', 'required' => false],
                ['type' => SurveyQuestionType::CHOICE, 'label' => 'また注文したいですか', 'options' => ['ぜひ注文したい', 'どちらとも言えない', '注文しない'], 'required' => false],
                ['type' => SurveyQuestionType::TEXT, 'label' => '改善してほしい点', 'required' => false],
            ],
        ],
        'registration' => [
            'label' => '入会時のプロフィール',
            'title' => 'お客さまアンケート',
            'description' => 'おすすめをお届けするため、好みを教えてください。',
            'questions' => [
                ['type' => SurveyQuestionType::MULTI_CHOICE, 'label' => '興味のあるもの', 'options' => ['新メニュー', 'お得なクーポン', 'イベント', '季節限定'], 'required' => false],
                ['type' => SurveyQuestionType::CHOICE, 'label' => 'ご来店の頻度', 'options' => ['週に1回以上', '月に1〜3回', '数か月に1回', '初めて'], 'required' => false],
                ['type' => SurveyQuestionType::CHOICE, 'label' => 'このお店を知ったきっかけ', 'options' => ['店頭', 'SNS', '友人・知人', '検索', 'その他'], 'required' => false],
            ],
        ],
    ];

    /** @return array<string, array{label: string, description: string, count: int, icon: string}> 雛形のキー => 選ぶカードに出す内容 */
    public static function summaries(): array
    {
        $icons = ['visit' => 'fa-store', 'nps' => 'fa-gauge-high', 'product' => 'fa-utensils', 'registration' => 'fa-user-plus'];
        $summaries = [];
        foreach (self::TEMPLATES as $key => $template) {
            $summaries[$key] = ['label' => $template['label'], 'description' => $template['description'], 'count' => count($template['questions']), 'icon' => $icons[$key] ?? 'fa-clipboard-list'];
        }

        return $summaries;
    }

    /** @return array{label: string, title: string, description: string, questions: list<Question>}|null */
    public static function find(string $key): ?array
    {
        return self::TEMPLATES[$key] ?? null;
    }
}
