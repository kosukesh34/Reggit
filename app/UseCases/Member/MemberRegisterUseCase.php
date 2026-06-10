<?php

namespace App\UseCases\Member;

use App\Services\MemberService;
use App\UseCases\Member\Data\MemberData;
use App\UseCases\Member\Data\MemberRegisterInput;
use App\UseCases\Member\Data\MemberRegisterOutput;

class MemberRegisterUseCase
{
    private const ALREADY_REGISTERED_MESSAGE = '既に会員登録済みです';

    public function __construct(
        private MemberService $memberService,
    ) {}

    public function handle(MemberRegisterInput $input): MemberRegisterOutput
    {
        $user = $input->user;

        try {
            $this->memberService->registerMember($user, $input->validated);
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
