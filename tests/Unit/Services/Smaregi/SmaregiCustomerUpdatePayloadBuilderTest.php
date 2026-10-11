<?php

namespace Tests\Unit\Services\Smaregi;

use App\Services\Smaregi\SmaregiCustomerUpdatePayloadBuilder;
use PHPUnit\Framework\TestCase;

class SmaregiCustomerUpdatePayloadBuilderTest extends TestCase
{
    private SmaregiCustomerUpdatePayloadBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new SmaregiCustomerUpdatePayloadBuilder;
    }

    public function test_patch_preserves_explicit_optional_clears_and_omits_missing_fields(): void
    {
        $data = ['name' => null, 'kana' => '', 'email' => null, 'phone' => '', 'postal_code' => null, 'birthday' => null, 'address' => null, 'sex' => null];
        $this->assertSame([], $this->builder->buildFromProfileData($data));
        $this->assertSame([
            'mailAddress' => null, 'phoneNumber' => null, 'postCode' => null, 'birthDate' => null,
            'lastName' => null, 'firstName' => null, 'lastKana' => null, 'firstKana' => null,
            'address' => null, 'sex' => '0',
        ], $this->builder->buildFromProfilePatchData($data));
        $this->assertSame(['phoneNumber' => '09012345678'], $this->builder->buildFromProfilePatchData(['phone' => '09012345678']));
        $this->assertSame(['address' => null], $this->builder->buildFromProfilePatchData(['prefecture' => null, 'city' => '', 'street' => null]));
    }

    public function test_不正なメールアドレスはスマレジに送らないこと(): void
    {
        $body = $this->builder->buildFromProfileData([
            'name' => '山田 太郎',
            'email' => 'not-an-email',
        ]);

        $this->assertArrayHasKey('lastName', $body);
        $this->assertArrayNotHasKey('mailAddress', $body);
    }

    public function test_空のメールアドレスはスマレジに送らないこと(): void
    {
        $body = $this->builder->buildFromProfileData([
            'name' => '山田 太郎',
            'email' => '',
        ]);

        $this->assertArrayNotHasKey('mailAddress', $body);
    }

    public function test_有効なメールアドレスはスマレジに送ること(): void
    {
        $body = $this->builder->buildFromProfileData([
            'email' => 'user@example.com',
        ]);

        $this->assertSame('user@example.com', $body['mailAddress']);
    }

    public function test_住所・生年月日・性別・案内メールをスマレジ項目に変換すること(): void
    {
        $body = $this->builder->buildFromProfileData([
            'postal_code' => '100-0001',
            'prefecture' => '東京都',
            'city' => '千代田区',
            'street' => '1-1',
            'birthday' => '1990-01-02',
            'sex' => '1',
            'mail_receive_flag' => '0',
        ]);

        $this->assertSame('100-0001', $body['postCode']);
        $this->assertSame('東京都千代田区1-1', $body['address']);
        $this->assertSame('1990-01-02', $body['birthDate']);
        $this->assertSame('1', $body['sex']);
        $this->assertSame('0', $body['mailReceiveFlag']);
    }

    public function test_任意項目はスマレジ自由項目として送ること(): void
    {
        $body = $this->builder->buildFromProfileData(
            [
                'nickname' => 'たろう',
                'colors' => ['赤', '青'],
            ],
            [
                ['key' => 'nickname', 'smaregi_no' => 1],
                ['key' => 'colors', 'smaregi_no' => 2],
            ],
        );

        $this->assertSame([
            ['no' => 1, 'value' => 'たろう'],
            ['no' => 2, 'value' => '赤、青'],
        ], $body['customFields']);
    }

    public function test_partial_profile_updates_do_not_send_unrelated_custom_fields(): void
    {
        $definitions = [['key' => 'nickname', 'smaregi_no' => 1], ['key' => 'company', 'smaregi_no' => 2]];
        $body = $this->builder->buildFromProfileData(['nickname' => 'Changed'], $definitions);
        $this->assertSame([['no' => 1, 'value' => 'Changed']], $body['customFields']);
        $emailOnly = $this->builder->buildFromProfileData(['email' => 'user@example.com'], $definitions);
        $this->assertArrayNotHasKey('customFields', $emailOnly);
    }

    public function test_explicit_null_custom_field_is_still_sent_for_clearing(): void
    {
        $body = $this->builder->buildFromProfileData(['nickname' => null], [['key' => 'nickname', 'smaregi_no' => 1]]);
        $this->assertSame([['no' => 1, 'value' => null]], $body['customFields']);
        $nested = $this->builder->buildFromProfileData(['profile_extra' => ['nickname' => null]], [['key' => 'nickname', 'smaregi_no' => 1]]);
        $this->assertSame($body['customFields'], $nested['customFields']);
    }

    public function test_profile_extraの任意項目もスマレジ自由項目として送ること(): void
    {
        $body = $this->builder->buildFromAdminMemberData(
            ['name' => '山田 太郎'],
            ['profile_extra' => ['company' => '株式会社レジット']],
            [['key' => 'company', 'smaregi_no' => 4]],
        );

        $this->assertSame([
            ['no' => 4, 'value' => '株式会社レジット'],
        ], $body['customFields']);
    }

    public function test_自由入力住所がある場合はそれを優先すること(): void
    {
        $body = $this->builder->buildFromProfileData([
            'address' => '東京都港区',
            'prefecture' => '大阪府',
            'city' => '大阪市',
        ]);

        $this->assertSame('東京都港区', $body['address']);
    }

    public function test_登録時は氏名カナ未入力でもプレースホルダを補うこと(): void
    {
        $body = $this->builder->withRegisterNamePlaceholders(
            $this->builder->buildFromProfileData([
                'phone' => '09012345678',
            ])
        );

        $this->assertSame('—', $body['lastName']);
        $this->assertSame('—', $body['firstName']);
        $this->assertSame('ー', $body['lastKana']);
        $this->assertSame('ー', $body['firstKana']);
        $this->assertSame('09012345678', $body['phoneNumber']);
    }
}
