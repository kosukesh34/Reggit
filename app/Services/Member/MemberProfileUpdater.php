<?php

namespace App\Services\Member;

use App\Domain\Settings\Repositories\MemberSettings;
use App\Enums\MemberNotificationPreference;
use App\Models\User;
use App\Services\Smaregi\SmaregiMemberProfileSyncService;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** 登録済み会員のプロフィール項目・プロフィール画像を更新し、更新後の会員データを返す。 */
class MemberProfileUpdater
{
    public function __construct(
        private MemberSettings $shopSettingRepository,
        private SmaregiMemberProfileSyncService $profileSync,
        private MemberDataReader $memberData,
    ) {}

    public function saveProfileImage(User $user, UploadedFile $image): array
    {
        return $this->writeProfileImage($user, $image);
    }

    public function deleteProfileImage(User $user): array
    {
        return $this->writeProfileImage($user, null);
    }

    public function updateProfile(User $user, array $data): array
    {
        $projectId = AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null);
        $member = $user->member()->where('project_id', $projectId)->first();
        if ($member === null) {
            throw ValidationException::withMessages(['member' => '会員登録が必要です']);
        }

        $settings = $this->shopSettingRepository->getProfileFieldSettings($projectId);

        $userUpdates = [];
        $memberUpdates = [];
        $extraUpdates = [];

        $userKeys = ['name', 'kana', 'email', 'phone'];
        foreach ($userKeys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $val = $data[$key];
            if ($key === 'email' && $val === '') {
                $val = null;
            }
            $userUpdates[$key] = $val === '' ? null : $val;
        }

        $memberKeys = ['birthday', 'address', 'postal_code', 'prefecture', 'city', 'street', 'sex', 'mail_receive_flag', 'customer_store_id', 'notification_preference'];
        foreach ($memberKeys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $val = $data[$key];
            if ($key === 'birthday') {
                $memberUpdates[$key] = $val ?: null;
            } elseif ($key === 'notification_preference') {
                $memberUpdates[$key] = MemberNotificationPreference::normalize(is_string($val) ? $val : null);
            } else {
                $memberUpdates[$key] = $val === '' ? null : $val;
            }
        }

        if (array_key_exists('address', $memberUpdates) && $memberUpdates['address'] === null
            && array_intersect(array_keys($data), ['prefecture', 'city', 'street']) === []) {
            $memberUpdates = array_merge($memberUpdates, ['prefecture' => null, 'city' => null, 'street' => null]);
        }

        $customKeys = array_column($settings['custom_fields'] ?? [], 'key');
        foreach ($customKeys as $key) {
            if (array_key_exists($key, $data)) {
                $extraUpdates[$key] = $data[$key] === '' ? null : $data[$key];
            }
        }

        DB::transaction(function () use ($user, $member, $projectId, $userUpdates, $memberUpdates, $extraUpdates): void {
            $lockedUser = User::query()->whereKey($user->id)->where('project_id', $projectId)->lockForUpdate()->first();
            if ($lockedUser === null
                || (int) $lockedUser->authentication_version !== (int) $user->authentication_version) {
                throw ValidationException::withMessages(['member' => '会員登録が必要です']);
            }
            $lockedMember = $lockedUser->member()->whereKey($member->id)->where('project_id', $projectId)->lockForUpdate()->first();
            if ($lockedMember === null) {
                throw ValidationException::withMessages(['member' => '会員登録が必要です']);
            }
            if ($userUpdates !== []) {
                $lockedUser->update($userUpdates);
            }
            if ($extraUpdates !== []) {
                $memberUpdates['profile_extra'] = array_merge($lockedMember->profile_extra ?? [], $extraUpdates);
            }
            if ($memberUpdates !== []) {
                $lockedMember->update($memberUpdates);
            }
            $keys = array_keys(array_merge($userUpdates, $memberUpdates, $extraUpdates));
            $keys = array_values(array_diff($keys, ['profile_extra']));
            if ($keys !== []) {
                $this->profileSync->record($lockedMember, $lockedUser, $keys);
            }
        });

        // The remote profile may still be stale while outbound synchronization is pending.
        return $this->memberData->getMemberData($user->fresh(['member']), syncFromSmaregi: false);
    }

    private function writeProfileImage(User $user, ?UploadedFile $image): array
    {
        $projectId = AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null);
        DB::transaction(function () use ($user, $image, $projectId): void {
            $owner = User::query()->where('project_id', $projectId)->whereKey($user->id)->lockForUpdate()->first();
            if ($owner === null || (int) $owner->authentication_version !== (int) $user->authentication_version) {
                throw ValidationException::withMessages(['image' => '会員登録が必要です']);
            }
            $member = $owner->member()->where('project_id', $projectId)->lockForUpdate()->first();
            $owner->setRelation('member', $member);
            $this->assertProfileImageAvailable($owner);
            $oldPath = $owner->profile_image_path;
            $path = null;
            try {
                if ($image !== null) {
                    $stored = $image->store('member-profile-images/'.$projectId, 'public');
                    if ($stored === false) {
                        throw new \RuntimeException('Failed to store image');
                    }
                    $path = $stored;
                }
                $owner->update(['profile_image_path' => $path]);
            } catch (\Throwable $exception) {
                if ($path !== null) {
                    Storage::disk('public')->delete($path);
                }
                throw $exception;
            }
            if ($oldPath !== null && $oldPath !== '' && $oldPath !== $path) {
                DB::afterCommit(function () use ($oldPath): void {
                    try {
                        Storage::disk('public')->delete($oldPath);
                    } catch (\Throwable $exception) {
                        LogHelper::warning('[Member] old profile image cleanup failed', [
                            'error' => $exception->getMessage(),
                        ]);
                    }
                });
            }
        }, 3);

        return $this->memberData->getMemberData($user->fresh(['member']));
    }

    private function assertProfileImageAvailable(User $user): void
    {
        $projectId = $user->member?->project_id;
        if ($projectId === null) {
            throw ValidationException::withMessages(['image' => '会員登録が必要です']);
        }
        $settings = $this->shopSettingRepository->getProfileFieldSettings((int) $projectId);
        if (MemberDataReader::profileImageEnabled($settings) !== true) {
            throw ValidationException::withMessages(['image' => 'プロフィール画像の設定は現在利用できません']);
        }
    }
}
