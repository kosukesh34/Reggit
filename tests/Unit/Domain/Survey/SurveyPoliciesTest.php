<?php

namespace Tests\Unit\Domain\Survey;

use App\Domain\Survey\Exceptions\InvalidSurveyAnswers;
use App\Domain\Survey\Policies\SurveyAnswerPolicy;
use App\Domain\Survey\Policies\SurveyResultPolicy;
use PHPUnit\Framework\TestCase;

class SurveyPoliciesTest extends TestCase
{
    private const QUESTIONS = [
        ['type' => 'nps', 'label' => 'おすすめ度', 'required' => true],
        ['type' => 'choice', 'label' => '目的', 'options' => ['ランチ', 'ディナー'], 'required' => false],
        ['type' => 'text', 'label' => 'ご意見', 'required' => false],
    ];

    public function test_回答を質問の並びに正規化しnps区分を決めること(): void
    {
        $policy = new SurveyAnswerPolicy;

        $clean = $policy->normalize(self::QUESTIONS, ['8', 'ディナー', '  ']);

        $this->assertSame([8, 'ディナー', null], $clean);
        $this->assertSame(8, $policy->npsScore(self::QUESTIONS, $clean));
        $this->assertSame(['NPS推奨者', 'NPS推奨者', 'NPS中立者', 'NPS中立者', 'NPS批判者'], array_map($policy->npsTag(...), [10, 9, 8, 7, 6]));
    }

    public function test_必須の未回答と範囲外の値をまとめて返すこと(): void
    {
        try {
            (new SurveyAnswerPolicy)->normalize(self::QUESTIONS, [11, '朝食', str_repeat('あ', 1001)]);
            $this->fail('InvalidSurveyAnswers was not thrown');
        } catch (InvalidSurveyAnswers $e) {
            $this->assertSame(['answers.0', 'answers.1', 'answers.2'], array_keys($e->errors));
        }
        $this->expectException(InvalidSurveyAnswers::class);
        (new SurveyAnswerPolicy)->normalize(self::QUESTIONS, []);
    }

    public function test_追加した形式を検証し選んだ選択肢のタグを返すこと(): void
    {
        $policy = new SurveyAnswerPolicy;
        $questions = [
            ['type' => 'multi_choice', 'label' => '目的', 'options' => ['A', 'B', 'C'], 'option_tags' => ['A' => 'タグA', 'C' => 'タグC'], 'required' => true],
            ['type' => 'rating', 'label' => '評価', 'required' => false],
            ['type' => 'number', 'label' => '人数', 'required' => false],
            ['type' => 'date', 'label' => '日付', 'required' => false],
        ];

        $clean = $policy->normalize($questions, [['C', 'A', 'A'], '5', '2.5', '2026-11-03']);

        $this->assertSame([['A', 'C'], 5, 2.5, '2026-11-03'], $clean);
        $this->assertSame(['タグA', 'タグC'], $policy->optionTags($questions, $clean));
        try {
            $policy->normalize($questions, [['D'], 6, 'abc', '2026-02-30']);
            $this->fail('InvalidSurveyAnswers was not thrown');
        } catch (InvalidSurveyAnswers $e) {
            $this->assertSame(['answers.0', 'answers.1', 'answers.2', 'answers.3'], array_keys($e->errors));
        }
    }

    public function test_csvの行で数式として実行される文字を無効にすること(): void
    {
        $policy = new SurveyResultPolicy;
        $questions = [['type' => 'text', 'label' => 'ご意見'], ['type' => 'multi_choice', 'label' => '目的', 'options' => ['A', 'B']], ['type' => 'number', 'label' => '人数']];

        $this->assertSame(["'=1+1", 'A、B', '-3'], $policy->csvRow($questions, ['=1+1', ['A', 'B'], -3]));
        $this->assertSame(['', '', ''], $policy->csvRow($questions, null));
    }

    public function test_質問ごとに集計すること(): void
    {
        $stats = (new SurveyResultPolicy)->summarize(self::QUESTIONS, [[10, 'ランチ', 'おいしい'], [9, 'ランチ', null], [3, 'ディナー', ''], null]);

        $this->assertSame(['promoters' => 2, 'passives' => 0, 'detractors' => 1, 'nps' => 33, 'average' => 7.3], array_intersect_key($stats[0], array_flip(['promoters', 'passives', 'detractors', 'nps', 'average'])));
        $this->assertSame([['label' => 'ランチ', 'count' => 2], ['label' => 'ディナー', 'count' => 1]], $stats[1]['options']);
        $this->assertSame(['おいしい'], $stats[2]['texts']);
        $this->assertNull((new SurveyResultPolicy)->summarize(self::QUESTIONS, [])[0]['nps']);
    }

    public function test_その他の自由入力は許可した質問だけ受け付けて集計で分けること(): void
    {
        $policy = new SurveyAnswerPolicy;
        $questions = [
            ['type' => 'choice', 'label' => '目的', 'options' => ['ランチ', 'ディナー'], 'allow_other' => true],
            ['type' => 'multi_choice', 'label' => 'きっかけ', 'options' => ['SNS', 'チラシ'], 'allow_other' => true],
            ['type' => 'choice', 'label' => '席', 'options' => ['店内', 'テラス']],
        ];

        $clean = $policy->normalize($questions, ['その他：テイクアウト', ['その他：友人', 'SNS'], '店内']);
        $this->assertSame(['その他：テイクアウト', ['SNS', 'その他：友人'], '店内'], $clean);
        foreach ([[null, null, 'その他：屋上'], ['その他：', null, null], ['その他：'.str_repeat('あ', 200), null, null]] as $answers) {
            try {
                $policy->normalize($questions, $answers);
                $this->fail('InvalidSurveyAnswers was not thrown');
            } catch (InvalidSurveyAnswers $e) {
                $this->assertCount(1, $e->errors);
            }
        }

        $stats = (new SurveyResultPolicy)->summarize($questions, [$clean, ['ランチ', ['SNS'], null]]);
        $this->assertSame([['label' => 'ランチ', 'count' => 1], ['label' => 'ディナー', 'count' => 0], ['label' => 'その他', 'count' => 1]], $stats[0]['options']);
        $this->assertSame(['テイクアウト'], $stats[0]['others']);
        $this->assertSame(['友人'], $stats[1]['others']);
    }
}
