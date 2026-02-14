<?php

namespace App\UseCases\Member;

use App\Services\MemberService;
use App\Services\UserService;
use App\UseCases\Member\Dto\MemberDataDto;
use App\UseCases\Member\Dto\MemberRegisterResultDto;
use Illuminate\Http\Request;

class MemberRegisterUseCase
{
    private const ALREADY_REGISTERED_MESSAGE = '既に会員登録済みです';

    public function __construct(
        private MemberService $memberService,
        private UserService $userService
    ) {
    }

    /** @param array<string, mixed> $validated */
    public function handle(Request $request, array $validated): MemberRegisterResultDto
    {
        $user = $this->userService->getOrCreateUser($request);

        try {
            $this->memberService->registerMember($user, $validated);
        } catch (\Exception $e) {
            if ($e->getMessage() !== self::ALREADY_REGISTERED_MESSAGE) {
                throw $e;
            }

            $user->refresh();
            $data = $this->memberService->getMemberData($user);
            return new MemberRegisterResultDto(new MemberDataDto($data), true);
        }

        $user->refresh();
        $data = $this->memberService->getMemberData($user);

        return new MemberRegisterResultDto(new MemberDataDto($data), false);
    }
}
