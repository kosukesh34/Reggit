<?php

namespace Tests\Support;

use App\Domain\Member\Repositories\MemberRepository;
use App\Models\Member;

trait GrantsMemberPoints
{
    protected function grantMemberPoints(Member $member, int $points, string $description = 'test'): void
    {
        if ($points <= 0) {
            return;
        }

        $repository = app(MemberRepository::class);
        $aggregate = $repository->findById((int) $member->id, (int) $member->project_id);
        if ($aggregate === null) {
            $this->fail('member aggregate not found for points grant');
        }

        $aggregate->earnPoints($points, $description, null, now()->addYear());
        $repository->save($aggregate);
        $member->refresh();
    }
}
