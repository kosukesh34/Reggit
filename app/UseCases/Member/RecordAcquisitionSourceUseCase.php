<?php

namespace App\UseCases\Member;

use App\Domain\Member\Repositories\AcquisitionSourceRepository;
use App\Domain\Member\Repositories\MemberRepository;
use App\Services\Member\MemberAutoTagger;

/** 経路QRから来た新規会員に登録元を記録し、経路でセグメント配信できるようタグを付ける。 */
class RecordAcquisitionSourceUseCase
{
    public const CODE_PATTERN = '/^[0-9a-f]{10}$/';

    public function __construct(
        private readonly MemberRepository $members,
        private readonly AcquisitionSourceRepository $sources,
        private readonly MemberAutoTagger $tagger,
    ) {}

    public function handle(int $projectId, int $userId, string $code): void
    {
        $member = $this->members->findByUserId($userId, $projectId);
        $source = $this->sources->findByCode($projectId, $code);
        if ($member === null || $source === null) {
            return;
        }
        $memberId = $member->id()->value();
        if ($this->sources->attachIfMissing($memberId, $source['id'])) {
            $this->tagger->assign($projectId, $memberId, $source['tag_name']);
        }
    }
}
