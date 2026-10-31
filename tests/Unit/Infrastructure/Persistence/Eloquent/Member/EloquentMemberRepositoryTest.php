<?php

namespace Tests\Unit\Infrastructure\Persistence\Eloquent\Member;

use App\Domain\Member\Aggregates\Member as MemberAggregate;
use App\Domain\Member\ValueObjects\MemberId;
use App\Infrastructure\Persistence\Eloquent\Member\EloquentMemberRepository;
use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\User;
use App\Services\Member\MemberPointLedger;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentMemberRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_by_id_読取だけでは台帳バックフィルしないこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'points' => 80,
        ]);

        $repository = app(EloquentMemberRepository::class);
        $aggregate = $repository->findById((int) $member->id, (int) $project->id);

        $this->assertNotNull($aggregate);
        $this->assertSame(0, $aggregate->displayPoints());
        $this->assertSame(0, PointTransaction::query()->where('member_id', $member->id)->count());
    }

    public function test_sync_points_ledger_明示呼び出しでバックフィルすること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'points' => 80,
        ]);

        $repository = app(EloquentMemberRepository::class);
        $repository->syncPointsLedger((int) $member->id, (int) $project->id);
        $aggregate = $repository->findById((int) $member->id, (int) $project->id);

        $this->assertNotNull($aggregate);
        $this->assertSame(80, $aggregate->displayPoints());
        $this->assertDatabaseHas('point_transactions', [
            'member_id' => $member->id,
            'points' => 80,
            'description' => MemberPointLedger::POINTS_LEDGER_BACKFILL_DESCRIPTION,
        ]);
    }

    public function test_find_by_id_会員をドメイン集約として返すこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'points' => 0,
        ]);
        PointTransaction::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'type' => 'earned',
            'points' => 40,
            'description' => '初期',
        ]);
        app(MemberPointLedger::class)->recalculate($member);

        $repository = app(EloquentMemberRepository::class);
        $aggregate = $repository->findById((int) $member->id, (int) $project->id);

        $this->assertNotNull($aggregate);
        $this->assertSame(40, $aggregate->displayPoints());
    }

    public function test_find_by_user_id_会員をドメイン集約として返すこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'points' => 0,
        ]);
        PointTransaction::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'type' => 'earned',
            'points' => 30,
            'description' => '初期',
        ]);
        app(MemberPointLedger::class)->recalculate($member);

        $repository = app(EloquentMemberRepository::class);
        $aggregate = $repository->findByUserId((int) $user->id, (int) $project->id);

        $this->assertNotNull($aggregate);
        $this->assertSame(30, $aggregate->displayPoints());
        $this->assertSame((int) $member->id, $aggregate->id()->value());
    }

    public function test_save_ポイント利用を永続化すること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'points' => 0,
        ]);
        PointTransaction::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'type' => 'earned',
            'points' => 50,
            'description' => '初期',
        ]);
        app(MemberPointLedger::class)->recalculate($member);

        $aggregate = new MemberAggregate(
            new MemberId((int) $member->id),
            (int) $project->id,
            50,
            '',
        );
        $aggregate->usePoints(15, '利用テスト', null);

        $repository = app(EloquentMemberRepository::class);
        $repository->save($aggregate);

        $member->refresh();
        $this->assertSame(35, app(MemberPointLedger::class)->displayPoints($member));
        $this->assertDatabaseHas('point_transactions', [
            'member_id' => $member->id,
            'points' => -15,
            'description' => '利用テスト',
        ]);
    }

    public function test_save_ポイント獲得を永続化すること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'points' => 0,
        ]);

        $aggregate = new MemberAggregate(
            new MemberId((int) $member->id),
            (int) $project->id,
            0,
            '',
        );
        $aggregate->earnPoints(25, 'リポジトリテスト', null, Carbon::parse('2026-12-31'));

        $repository = app(EloquentMemberRepository::class);
        $repository->save($aggregate);

        $member->refresh();
        $this->assertSame(25, app(MemberPointLedger::class)->displayPoints($member));
        $this->assertDatabaseHas('point_transactions', [
            'member_id' => $member->id,
            'points' => 25,
            'description' => 'リポジトリテスト',
        ]);
    }
}
