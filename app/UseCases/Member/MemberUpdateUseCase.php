<?php

namespace App\UseCases\Member;

use App\Services\MemberService;
use App\Services\UserService;
use App\UseCases\Member\Dto\MemberDataDto;
use Illuminate\Http\Request;

class MemberUpdateUseCase
{
    public function __construct(
        private MemberService $memberService,
        private UserService $userService
    ) {
    }

    /** @param array<string, mixed> $validated */
    public function handle(Request $request, array $validated): MemberDataDto
    {
        $user = $this->userService->getOrCreateUser($request);
        $data = $this->memberService->updateProfile($user, $validated);

        return new MemberDataDto($data);
    }
}
