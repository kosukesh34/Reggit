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
