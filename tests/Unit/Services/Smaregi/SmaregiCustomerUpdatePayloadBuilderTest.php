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
}
