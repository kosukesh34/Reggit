<?php

namespace App\UseCases\Member;

use App\Services\Member\MemberDataReader;
use App\Services\Member\MemberRegistrar;
use App\UseCases\Member\Data\MemberData;
use App\UseCases\Member\Data\MemberRegisterInput;
use App\UseCases\Member\Data\MemberRegisterOutput;

class MemberRegisterUseCase
{
    private const ALREADY_REGISTERED_MESSAGE = '既に会員登録済みです';

    public function __construct(
        private MemberRegistrar $memberRegistrar,
        private MemberDataReader $memberData,
    ) {}

    public function handle(MemberRegisterInput $input): MemberRegisterOutput
    {
        try {
            $this->memberRegistrar->registerMember($input->user, $input->profilePayload);
        } catch (\Exception $e) {
            if ($e->getMessage() !== self::ALREADY_REGISTERED_MESSAGE) {
                throw $e;
            }

            $input->user->refresh();
            $data = $this->memberData->getMemberData($input->user);

            return new MemberRegisterOutput(new MemberData($data), true);
        }

        $input->user->refresh();
        $data = $this->memberData->getMemberData($input->user);

        return new MemberRegisterOutput(new MemberData($data), false);
    }
}
