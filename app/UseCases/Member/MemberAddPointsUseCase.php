<?php

namespace App\UseCases\Member;

use App\Domain\Member\Events\PointsEarned;
use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\UseCases\Member\Data\MemberAddPointsInput;
use App\UseCases\Member\Data\MemberPointsOutput;

class MemberAddPointsUseCase
{
    public function __construct(
        private MemberRepository $memberRepository,
        private ShopSettingRepository $shopSettingRepository,
        private DomainEventDispatcher $domainEvents,
    ) {}

    public function handle(MemberAddPointsInput $input): MemberPointsOutput
    {
        $projectId = (int) ($input->user->project_id ?? 1);
        $member = $this->memberRepository->findByUserId((int) $input->user->id, $projectId);

        if ($member === null) {
            return new MemberPointsOutput(false, 0, collect());
        }

        $this->memberRepository->syncPointsLedger($member->id()->value(), $projectId);
        $member = $this->memberRepository->findById($member->id()->value(), $projectId);
        if ($member === null) {
            return new MemberPointsOutput(false, 0, collect());
        }

        $validDays = $this->shopSettingRepository->getPointsValidDays($member->projectId());
        $member->earnPoints(
            $input->points,
            $input->description,
            null,
            now()->addDays($validDays),
        );
        $this->memberRepository->save($member);

        if ($input->points > 0) {
            $this->domainEvents->dispatch(new PointsEarned(
                $member->id()->value(),
                $member->projectId(),
                $input->points,
                null,
            ));
        }

        return new MemberPointsOutput(true, $member->displayPoints(), collect());
    }
}
