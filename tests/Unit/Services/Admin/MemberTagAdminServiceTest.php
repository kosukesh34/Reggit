<?php

namespace Tests\Unit\Services\Admin;

use App\Models\Member;
use App\Models\MemberTag;
use App\Models\Project;
use App\Models\User;
use App\Services\Admin\MemberTagAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberTagAdminServiceTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private MemberTagAdminService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Test', 'description' => 'Test', 'is_active' => true]
        );
        $this->service = new MemberTagAdminService;
    }

    public function test_create_tag_タグが作成される(): void
    {
        $tag = $this->service->createTag($this->project->id, 'VIP', '#ff0000');

        $this->assertSame('VIP', $tag->name);
        $this->assertSame('#ff0000', $tag->color);
        $this->assertSame($this->project->id, $tag->project_id);
    }

    public function test_attach_and_detach_tag_会員にタグを付与・解除できる(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);
        $member = Member::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $user->id,
        ]);
        $tag = MemberTag::create([
            'project_id' => $this->project->id,
            'name' => 'テスト',
            'color' => '#0d9488',
        ]);

        $this->service->attachTagToMember($member, $tag->id);
        $this->assertTrue($member->tags()->where('member_tags.id', $tag->id)->exists());

        $this->service->detachTagFromMember($member, $tag->id);
        $this->assertFalse($member->tags()->where('member_tags.id', $tag->id)->exists());
    }

    public function test_attach_tag_他プロジェクトのタグは付与できない(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $otherProject = Project::firstOrCreate(
            ['project_key' => 'other'],
            ['name' => 'Other', 'description' => 'Other', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => $this->project->id]);
        $member = Member::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $user->id,
        ]);
        $otherTag = MemberTag::create([
            'project_id' => $otherProject->id,
            'name' => '他プロジェクト',
            'color' => '#000000',
        ]);

        $this->service->attachTagToMember($member, $otherTag->id);
    }

    public function test_update_memo_メモが保存される(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);
        $member = Member::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $user->id,
        ]);

        $this->service->updateMemo($member, 'テストメモです');

        $this->assertSame('テストメモです', $member->fresh()->memo);
    }

    public function test_destroy_tag_タグが削除される(): void
    {
        $tag = $this->service->createTag($this->project->id, '削除テスト', '#aabbcc');

        $this->service->destroyTag($this->project->id, $tag->id);

        $this->assertNull(MemberTag::find($tag->id));
    }
}
