<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ProfileSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\Member\MemberProfileUpdater;
use App\Util\AdminContext;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class MemberProfileImageLifecycleTest extends TestCase
{
    use DatabaseTruncation;

    #[BeforeClass]
    public static function resetMigrationBeforeClass(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    #[AfterClass]
    public static function resetMigrationAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    private function imageOwner(): User
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $settings = app(ProfileSettingStore::class)->getProfileFieldSettings((int) $project->id);
        $settings['smaregi_fields']['profile_image']['enabled'] = true;
        app(ProfileSettingStore::class)->setProfileFieldSettings($settings, (int) $project->id);
        $path = 'member-profile-images/'.$project->id.'/old.png';
        Storage::disk('public')->put($path, 'old image');
        $user = User::factory()->create(['project_id' => $project->id, 'profile_image_path' => $path, 'authentication_version' => 1]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);

        return $user;
    }

    public function test_failed_database_save_keeps_old_image_and_removes_new_file(): void
    {
        $user = $this->imageOwner();
        $oldPath = $user->profile_image_path;
        Event::listen('eloquent.updating: '.User::class, function (): void {
            throw new RuntimeException('image database save failed');
        });
        try {
            app(MemberProfileUpdater::class)->saveProfileImage($user, UploadedFile::fake()->image('new.png'));
            $this->fail('Expected image database save failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('image database save failed', $exception->getMessage());
        }
        $this->assertSame($oldPath, $user->fresh()->profile_image_path);
        Storage::disk('public')->assertExists($oldPath);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_old_file_cleanup_failure_does_not_remove_committed_new_image(): void
    {
        $user = $this->imageOwner();
        $oldPath = $user->profile_image_path;
        $disk = Storage::disk('public');
        $failingDisk = Mockery::mock(FilesystemAdapter::class, [$disk->getDriver(), $disk->getAdapter(), []])->makePartial();
        $failingDisk->shouldReceive('delete')->once()->with($oldPath)->andThrow(new RuntimeException('old image cleanup failed'));
        Storage::set('public', $failingDisk);
        app(MemberProfileUpdater::class)->saveProfileImage($user, UploadedFile::fake()->image('new.png'));
        $path = $user->fresh()->profile_image_path;
        $this->assertNotSame($oldPath, $path);
        $this->assertNotNull($path);
        $this->assertTrue($disk->exists($path));
        $this->assertTrue($disk->exists($oldPath));
    }

    #[TestWith(['upload'])]
    #[TestWith(['delete'])]
    public function test_outer_rollback_keeps_old_image(string $action): void
    {
        $user = $this->imageOwner();
        $oldPath = $user->profile_image_path;
        DB::beginTransaction();
        try {
            if ($action === 'upload') {
                app(MemberProfileUpdater::class)->saveProfileImage($user, UploadedFile::fake()->image('new.png'));
            } else {
                app(MemberProfileUpdater::class)->deleteProfileImage($user);
            }
            Storage::disk('public')->assertExists($oldPath);
        } finally {
            DB::rollBack();
        }
        $this->assertSame($oldPath, $user->fresh()->profile_image_path);
        Storage::disk('public')->assertExists($oldPath);
    }

    public function test_current_generation_can_replace_and_delete_image_after_commit(): void
    {
        $user = $this->imageOwner();
        $oldPath = $user->profile_image_path;
        app(MemberProfileUpdater::class)->saveProfileImage($user, UploadedFile::fake()->image('new.png'));
        $path = $user->fresh()->profile_image_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($path);
        app(MemberProfileUpdater::class)->deleteProfileImage($user->fresh());
        $this->assertNull($user->fresh()->profile_image_path);
        Storage::disk('public')->assertMissing($path);
    }
}
