<?php

namespace App\UseCases\Admin\Survey\Data;

/**
 * 管理画面で保存するアンケート。
 *
 * @phpstan-import-type Question from \App\Domain\Survey\Policies\SurveyAnswerPolicy
 */
final class SurveyInput
{
    /** @param list<Question> $questions */
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly int $rewardPoints,
        public readonly ?string $inviteTrigger,
        public readonly int $inviteDelayDays,
        public readonly bool $isActive,
        public readonly ?string $startsAt,
        public readonly ?string $endsAt,
        public readonly array $questions,
    ) {}

    /** @return array<string, mixed> 質問以外の項目 */
    public function settings(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'reward_points' => $this->rewardPoints,
            'invite_trigger' => $this->inviteTrigger,
            'invite_delay_days' => $this->inviteDelayDays,
            'is_active' => $this->isActive,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
        ];
    }
}
