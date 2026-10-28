<?php

namespace App\Domain\Survey\Policies;

use App\Domain\Survey\SurveyQuestionType;

/**
 * 回答を質問ごとに集計し、CSV 用の1行に整える。
 *
 * @phpstan-import-type Question from SurveyAnswerPolicy
 */
final class SurveyResultPolicy
{
    private const TEXT_LIMIT = 30;

    /**
     * @param  list<Question>  $questions
     * @param  list<array<array-key, mixed>|null>  $answers  回答者ごとの回答
     * @return list<array<string, mixed>>
     */
    public function summarize(array $questions, array $answers): array
    {
        $stats = [];
        foreach ($questions as $index => $question) {
            $values = array_values(array_filter(
                array_map(fn ($row) => is_array($row) ? ($row[$index] ?? null) : null, $answers),
                fn ($value) => $value !== null && $value !== '' && $value !== [],
            ));
            $stats[] = ['question' => $question, 'count' => count($values)] + match ($question['type']) {
                SurveyQuestionType::NPS => $this->nps($values),
                SurveyQuestionType::CHOICE, SurveyQuestionType::MULTI_CHOICE => $this->choices($question['options'] ?? [], $values),
                SurveyQuestionType::RATING => $this->rating($values),
                SurveyQuestionType::NUMBER => $this->number($values),
                default => ['texts' => array_slice(array_map('strval', $values), 0, self::TEXT_LIMIT)],
            };
        }

        return $stats;
    }

    /**
     * @param  list<Question>  $questions
     * @return list<string> 質問の並びの見出し
     */
    public function csvHeader(array $questions): array
    {
        return array_map(fn (array $question) => $question['label'], $questions);
    }

    /**
     * @param  list<Question>  $questions
     * @param  array<array-key, mixed>|null  $answers
     * @return list<string>
     */
    public function csvRow(array $questions, ?array $answers): array
    {
        return array_map(function (int $index) use ($answers): string {
            $value = $answers[$index] ?? null;
            if (is_array($value)) {
                return $this->safeText(implode('、', $value));
            }

            return is_string($value) ? $this->safeText($value) : (string) ($value ?? '');
        }, array_keys($questions));
    }

    /** 会員が入力した文字が表計算ソフトで数式として実行されないようにする */
    private function safeText(string $text): string
    {
        return preg_match('/^[=+\-@\t\r]/', $text) === 1 ? "'".$text : $text;
    }

    /**
     * @param  list<mixed>  $values
     * @return array{promoters: int, passives: int, detractors: int, nps: ?int, average: ?float}
     */
    private function nps(array $values): array
    {
        $scores = array_map('intval', $values);
        $promoters = count(array_filter($scores, fn (int $score) => $score >= 9));
        $detractors = count(array_filter($scores, fn (int $score) => $score <= 6));
        $total = count($scores);

        return [
            'promoters' => $promoters,
            'passives' => $total - $promoters - $detractors,
            'detractors' => $detractors,
            'nps' => $total === 0 ? null : (int) round(($promoters - $detractors) * 100 / $total),
            'average' => $total === 0 ? null : round(array_sum($scores) / $total, 1),
        ];
    }

    /**
     * 複数選択は回答者ごとに選んだ選択肢をすべて数える。「その他：…」は「その他」にまとめ、書かれた内容も返す。
     *
     * @param  list<string>  $options
     * @param  list<mixed>  $values
     * @return array{options: list<array{label: string, count: int}>, others: list<string>}
     */
    private function choices(array $options, array $values): array
    {
        $selected = array_merge(...array_map(fn ($value) => (array) $value, $values ?: [[]]));
        $others = array_values(array_filter($selected, fn ($item) => is_string($item) && str_starts_with($item, SurveyAnswerPolicy::OTHER_PREFIX)));
        $counts = array_map(fn (string $option) => ['label' => $option, 'count' => count(array_keys($selected, $option, true))], $options);
        if ($others !== []) {
            $counts[] = ['label' => 'その他', 'count' => count($others)];
        }

        return [
            'options' => $counts,
            'others' => array_slice(array_map(fn (string $other) => mb_substr($other, mb_strlen(SurveyAnswerPolicy::OTHER_PREFIX)), $others), 0, self::TEXT_LIMIT),
        ];
    }

    /**
     * @param  list<mixed>  $values
     * @return array{average: ?float, distribution: array<int, int>}
     */
    private function rating(array $values): array
    {
        $scores = array_map('intval', $values);
        $distribution = [];
        for ($score = SurveyQuestionType::RATING_MAX; $score >= 1; $score--) {
            $distribution[$score] = count(array_keys($scores, $score, true));
        }

        return ['average' => $scores === [] ? null : round(array_sum($scores) / count($scores), 1), 'distribution' => $distribution];
    }

    /**
     * @param  list<mixed>  $values
     * @return array{average: ?float, min: int|float|null, max: int|float|null}
     */
    private function number(array $values): array
    {
        $numbers = array_map(fn ($value) => $value + 0, $values);

        return [
            'average' => $numbers === [] ? null : round(array_sum($numbers) / count($numbers), 2),
            'min' => $numbers === [] ? null : min($numbers),
            'max' => $numbers === [] ? null : max($numbers),
        ];
    }
}
