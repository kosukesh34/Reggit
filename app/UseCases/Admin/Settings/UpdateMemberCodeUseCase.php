<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\MemberCodeSettings;
use App\UseCases\Admin\Settings\Data\MemberCodeSettingsInput;

class UpdateMemberCodeUseCase
{
    public function __construct(private MemberCodeSettings $settings) {}

    public function handle(MemberCodeSettingsInput $input): void
    {
        foreach (['member_code_type' => $input->type, 'member_code_format' => $input->format, 'member_code_prefix' => $input->prefix] as $key => $value) {
            $this->settings->setValue($key, $value, 'string', $input->projectId);
        }
        $this->settings->setValue('member_code_length', $input->length, 'integer', $input->projectId);
    }
}
