<?php

namespace App\Domain\Survey\Policies;

use App\Domain\Survey\Exceptions\InvalidSurveyAnswers;
use App\Domain\Survey\SurveyQuestionType;

/**
 * 回答を質問の並びに合わせて検証・正規化し、NPS の区分と付けるタグを決める。
 *
 * @phpstan-type Question array{type: string, label: string, help?: string, options?: list<string>, option_tags?: array<string, string>, allow_other?: bool, required?: bool}
 * @phpstan-type Answer int|float|string|list<string>|null
 */
final class SurveyAnswerPolicy
{
    public const NPS_TAGS = ['NPS推奨者', 'NPS中立者', 'NPS批判者'];

    /** 「その他」を選んで書いた回答の先頭。集計では「その他」にまとめる */
    public const OTHER_PREFIX = 'その他：';

    private const OTHER_MAX = 200;

    private const NUMBER_LIMIT = 1_000_000_000;

    private const TEXT_MAX = 1000;

    /**
     * @param  list<Question>  $questions
     * @param  array<array-key, mixed>  $answers  質問の並び順と同じ添字の回答
     * @return list<Answer>
     *
     * @throws InvalidSurveyAnswers
     */
    public function normalize(array $questions, array $answers): array
    {
        $clean = [];
        $errors = [];
        foreach ($questions as $index => $question) {
            $value = $this->clean($question, $answers[$index] ?? null);
            if ($value === null || $value === []) {
                if ($question['required'] ?? false) {
                    $errors["answers.$index"] = "「{$question['label']}」に回答してください。";
                }
                $clean[] = null;

                continue;
            }
            if ($value === false) {
                $errors["answers.$index"] = "「{$question['label']}」の回答を確認してください。";
                $value = null;
            }
            $clean[] = $value;
        }
        if ($errors !== []) {
            throw new InvalidSurveyAnswers($errors);
        }

        return $clean;
    }

    /**
     * @param  list<Question>  $questions
     * @param  list<Answer>  $answers
     */
    public function npsScore(array $questions, array $answers): ?int
    {
        foreach ($questions as $index => $question) {
            if ($question['type'] === SurveyQuestionType::NPS && is_int($answers[$index] ?? null)) {
                return $answers[$index];
            }
        }

        return null;
    }

    /** NPS の区分（推奨者 9〜10・中立者 7〜8・批判者 0〜6）をタグ名で返す */
    public function npsTag(int $score): string
    {
        return match (true) {
            $score >= 9 => self::NPS_TAGS[0],
            $score >= 7 => self::NPS_TAGS[1],
            default => self::NPS_TAGS[2],
        };
    }

    /**
     * 選んだ選択肢に設定されたタグ。
     *
     * @param  list<Question>  $questions
     * @param  list<Answer>  $answers
     * @return list<string>
     */
    public function optionTags(array $questions, array $answers): array
    {
        $tags = [];
        foreach ($questions as $index => $question) {
            foreach ((array) ($answers[$index] ?? []) as $selected) {
                $tag = $question['option_tags'][$selected] ?? null;
                if (is_string($tag) && $tag !== '') {
                    $tags[] = $tag;
                }
            }
        }

        return array_values(array_unique($tags));
    }

    /**
     * 未回答は null、形式の誤りは false を返す。
     *
     * @param  Question  $question
     * @return Answer|false
     */
    private function clean(array $question, mixed $raw): int|float|string|array|false|null
    {
        $value = is_string($raw) ? trim($raw) : $raw;
        if ($value === '' || $value === null) {
            return null;
        }
        $options = $question['options'] ?? [];

        return match ($question['type']) {
            SurveyQuestionType::NPS => $this->integerBetween($value, 0, 10),
            SurveyQuestionType::RATING => $this->integerBetween($value, 1, SurveyQuestionType::RATING_MAX),
            SurveyQuestionType::CHOICE => is_string($value) && $this->isOption($question, $value) ? $value : false,
            SurveyQuestionType::MULTI_CHOICE => $this->multiChoice($value, $question),
            SurveyQuestionType::NUMBER => is_numeric($value) && abs((float) $value) <= self::NUMBER_LIMIT ? $value + 0 : false,
            SurveyQuestionType::DATE => is_string($value) && \DateTimeImmutable::createFromFormat('!Y-m-d', $value)?->format('Y-m-d') === $value ? $value : false,
            default => is_string($value) && mb_strlen($value) <= self::TEXT_MAX ? $value : false,
        };
    }

    private function integerBetween(mixed $value, int $min, int $max): int|false
    {
        $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);

        return is_int($int) ? $int : false;
    }

    /**
     * 選択肢か、「その他」が使える質問で「その他：…」と書いた回答か
     *
     * @param  Question  $question
     */
    private function isOption(array $question, string $value): bool
    {
        if (in_array($value, $question['options'] ?? [], true)) {
            return true;
        }

        return ($question['allow_other'] ?? false) && str_starts_with($value, self::OTHER_PREFIX)
            && trim(mb_substr($value, mb_strlen(self::OTHER_PREFIX))) !== '' && mb_strlen($value) <= self::OTHER_MAX;
    }

    /**
     * @param  Question  $question
     * @return list<string>|false
     */
    private function multiChoice(mixed $value, array $question): array|false
    {
        $options = $question['options'] ?? [];
        if (! is_array($value)) {
            return false;
        }
        $selected = [];
        foreach ($value as $item) {
            if ($item === null || $item === '') {
                continue;
            }
            // 入れ子の配列などは比較の前に弾き、500 ではなく入力エラーにする
            if (! is_string($item) || ! $this->isOption($question, $item)) {
                return false;
            }
            $selected[] = $item;
        }

        // 選択肢の並び順で保存し（「その他」は最後）、集計と CSV を読みやすくする
        return [...array_values(array_intersect($options, $selected)), ...array_values(array_unique(array_diff($selected, $options)))];
    }
}
