<?php

namespace App\Domain\Survey\ValueObjects;

/**
 * 回答・回答依頼で使うアンケートの定義。
 *
 * @phpstan-import-type Question from \App\Domain\Survey\Policies\SurveyAnswerPolicy
 */
final class SurveyDefinition
{
    /** @param list<Question> $questions */
    public function __construct(
        public readonly int $id,
        public readonly int $projectId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly array $questions,
        public readonly int $rewardPoints,
        /** 自動の回答依頼のきっかけ（SurveyInviteTrigger）。NULLは送らない */
        public readonly ?string $inviteTrigger,
        public readonly int $inviteDelayDays,
        public readonly bool $isActive,
        public readonly ?\DateTimeImmutable $startsAt,
        public readonly ?\DateTimeImmutable $endsAt,
        public readonly \DateTimeImmutable $createdAt,
    ) {}

    public function isOpen(\DateTimeInterface $now): bool
    {
        return $this->isActive
            && ($this->startsAt === null || $this->startsAt <= $now)
            && ($this->endsAt === null || $this->endsAt > $now);
    }

    /** アプリ内でこのアンケートを開くパス */
    public function appPath(): string
    {
        return '/survey?survey='.$this->id;
    }
}
