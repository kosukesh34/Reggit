<?php

namespace App\Http\Requests\Admin;

use App\Domain\Survey\SurveyInviteTrigger;
use App\Domain\Survey\SurveyQuestionType;
use App\UseCases\Admin\Survey\Data\SurveyInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * アンケートの作成・更新。画面の質問ビルダーで並べた質問（最大 MAX_QUESTIONS 問）を受け付ける。
 *
 * 選択肢は1行ずつ「選択肢」と「選んだ人に付けるタグ（任意）」を送る。
 */
class SurveyRequest extends FormRequest
{
    public const MAX_QUESTIONS = 30;

    public const MAX_OPTIONS = 30;

    public const DEFAULT_NPS_LABEL = 'このお店を友人や知人にすすめる可能性はどのくらいありますか？';

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'reward_points' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'invite_trigger' => ['nullable', Rule::in(array_keys(SurveyInviteTrigger::getValues()))],
            'invite_delay_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'questions' => ['nullable', 'array', 'max:'.self::MAX_QUESTIONS],
            'questions.*.type' => ['nullable', Rule::in(SurveyQuestionType::addable())],
            'questions.*.label' => ['nullable', 'string', 'max:200'],
            'questions.*.help' => ['nullable', 'string', 'max:300'],
            'questions.*.required' => ['nullable', 'boolean'],
            'questions.*.allow_other' => ['nullable', 'boolean'],
            'questions.*.options' => ['nullable', 'array', 'max:'.self::MAX_OPTIONS],
            'questions.*.options.*.label' => ['nullable', 'string', 'max:100'],
            'questions.*.options.*.tag' => ['nullable', 'string', 'max:50'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['questions.*.label' => '質問文', 'questions.*.help' => '補足説明', 'questions.*.options.*.label' => '選択肢', 'questions.*.options.*.tag' => 'タグ'];
    }

    /** 選択式の選択肢の数と、NPS が1問までか。質問が1つ以上あるかは、回答の有無で変更可否が変わるため UseCase で判定する */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $questions = $this->questions();
            foreach ($questions as $slot => $question) {
                if (SurveyQuestionType::hasOptions($question['type']) && count($question['options'] ?? []) < 2) {
                    $validator->errors()->add("questions.$slot.options", "質問「{$question['label']}」の選択肢を2つ以上入力してください。");
                }
            }
            if (count(array_filter($questions, fn (array $question) => $question['type'] === SurveyQuestionType::NPS)) > 1) {
                $validator->errors()->add('questions', 'NPSの質問は1つまでです。');
            }
        }];
    }

    public function toInput(): SurveyInput
    {
        $trigger = $this->validated('invite_trigger');

        return new SurveyInput(
            title: (string) $this->validated('title'),
            description: $this->validated('description'),
            rewardPoints: (int) $this->validated('reward_points', 0),
            inviteTrigger: is_string($trigger) && $trigger !== '' ? $trigger : null,
            inviteDelayDays: (int) $this->validated('invite_delay_days', 1),
            isActive: $this->boolean('is_active'),
            startsAt: $this->validated('starts_at'),
            endsAt: $this->validated('ends_at'),
            questions: array_values($this->questions()),
        );
    }

    /** @return array<int|string, array{type: string, label: string, help?: string, options?: list<string>, option_tags?: array<string, string>, allow_other?: bool, required: bool}> 入力欄の番号 => 質問 */
    private function questions(): array
    {
        $questions = [];
        foreach ((array) $this->input('questions', []) as $slot => $row) {
            $type = (string) ($row['type'] ?? '');
            $label = trim((string) ($row['label'] ?? ''));
            if ($type === SurveyQuestionType::NPS && $label === '') {
                $label = self::DEFAULT_NPS_LABEL;
            }
            if (! in_array($type, SurveyQuestionType::addable(), true) || $label === '') {
                continue;
            }
            $question = ['type' => $type, 'label' => $label, 'required' => (bool) ($row['required'] ?? false)];
            $help = trim((string) ($row['help'] ?? ''));
            if ($help !== '') {
                $question['help'] = $help;
            }
            if (SurveyQuestionType::hasOptions($type)) {
                $question += self::options((array) ($row['options'] ?? []));
                if ($row['allow_other'] ?? false) {
                    $question['allow_other'] = true;
                }
            }
            $questions[$slot] = $question;
        }

        return $questions;
    }

    /**
     * @param  array<array-key, mixed>  $rows
     * @return array{options: list<string>, option_tags?: array<string, string>}
     */
    private static function options(array $rows): array
    {
        $options = [];
        $tags = [];
        foreach ($rows as $row) {
            $option = mb_substr(trim((string) (is_array($row) ? ($row['label'] ?? '') : $row)), 0, 100);
            if ($option === '' || in_array($option, $options, true)) {
                continue;
            }
            $options[] = $option;
            $tag = trim((string) (is_array($row) ? ($row['tag'] ?? '') : ''));
            if ($tag !== '') {
                $tags[$option] = mb_substr($tag, 0, 50);
            }
        }

        return $tags === [] ? ['options' => $options] : ['options' => $options, 'option_tags' => $tags];
    }
}
