<?php

namespace Tests\Unit\UseCases\Admin\Settings;

use App\UseCases\Admin\Settings\Data\UpdateProfileSettingsInput;
use PHPUnit\Framework\TestCase;

class UpdateProfileSettingsInputTest extends TestCase
{
    public function test_from_validated_でカスタム項目キーを正規化する(): void
    {
        $input = UpdateProfileSettingsInput::fromValidated([
            'smaregi_fields' => [
                'name' => ['enabled' => true, 'required' => true],
                'phone' => ['enabled' => false, 'required' => false],
            ],
            'custom_fields' => [
                ['key' => 'nick-name!', 'label' => 'ニックネーム', 'required' => true],
            ],
        ]);

        $this->assertTrue($input->smaregiFields['name']['enabled']);
        $this->assertTrue($input->smaregiFields['name']['required']);
        $this->assertFalse($input->smaregiFields['phone']['enabled']);
        $this->assertSame('nickname', $input->customFields[0]['key']);
        $this->assertSame('ニックネーム', $input->customFields[0]['label']);
        $this->assertTrue($input->customFields[0]['required']);
    }
}
