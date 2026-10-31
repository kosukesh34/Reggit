<?php

namespace App\UseCases\Member;

use App\Models\User;
use App\Services\Member\MemberProfileUpdater;
use App\UseCases\Member\Data\MemberData;
use Illuminate\Http\UploadedFile;

class MemberProfileImageUseCase
{
    public function __construct(
        private MemberProfileUpdater $profileUpdater,
    ) {}

    public function store(User $user, UploadedFile $image): MemberData
    {
        return new MemberData($this->profileUpdater->saveProfileImage($user, $image));
    }

    public function destroy(User $user): MemberData
    {
        return new MemberData($this->profileUpdater->deleteProfileImage($user));
    }
}
