<?php

namespace App\UseCases\Member;

use App\Services\MemberService;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberData;
use App\UseCases\Member\Data\MemberRegisterOutput;
use Illuminate\Http\Request;

class MemberRegisterUseCase
{
    private const ALREADY_REGISTERED_MESSAGE = '既に会員登録済みです';

    public function __construct(
        private MemberService $memberService,
        private UserService $userService
    ) {
    }

    /** @param array<string, string|int|float|bool|array|null> $validated 検証済み会員登録データ（name, phone, email 等） */
    public function handle(Request $request, array $validated): MemberRegisterOutput
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
            return new MemberRegisterOutput(new MemberData($data), true);
        }

        $user->refresh();
        $data = $this->memberService->getMemberData($user);

        return new MemberRegisterOutput(new MemberData($data), false);
    }
}
