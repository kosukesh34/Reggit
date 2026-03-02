<?php

namespace App\UseCases\Member;

use App\Services\MemberService;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberData;
use Illuminate\Http\Request;

class MemberUpdateUseCase
{
    public function __construct(
        private MemberService $memberService,
        private UserService $userService
    ) {}

    /** @param array<string, string|int|float|bool|array|null> $validated 検証済み会員更新データ */
    public function handle(Request $request, array $validated): MemberData
    {
        $user = $this->userService->getOrCreateUser($request);
        $data = $this->memberService->updateProfile($user, $validated);

        return new MemberData($data);
    }
}
