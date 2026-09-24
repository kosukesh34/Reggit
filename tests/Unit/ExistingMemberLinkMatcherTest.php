<?php

namespace Tests\Unit;

use App\Services\ExistingMemberLinkMatcher;
use InvalidArgumentException;
use Tests\TestCase;

class ExistingMemberLinkMatcherTest extends TestCase
{
    public function test_選んだ項目がすべて一致する会員だけ返すこと(): void
    {
        $customer = $this->customer();
        $matched = (new ExistingMemberLinkMatcher)->match(
            [$customer, $this->customer(['phoneNumber' => '09000000000'])],
            ['customer_code', 'phone', 'name'],
            ['customer_code' => 'C100', 'phone' => '090-1111-2222', 'name' => '山田 太郎'],
        );

        $this->assertSame('9001', $matched['customerId']);
    }

    public function test_一致が0件または複数なら拒否すること(): void
    {
        $matcher = new ExistingMemberLinkMatcher;
        $this->expectException(InvalidArgumentException::class);
        $matcher->match([$this->customer()], ['email'], ['email' => 'other@example.com']);
    }

    /** @param  array<string, string>  $overrides */
    private function customer(array $overrides = []): array
    {
        return array_merge([
            'customerId' => '9001',
            'customerCode' => 'C100',
            'mailAddress' => 'a@example.com',
            'phoneNumber' => '09011112222',
            'lastName' => '山田',
            'firstName' => '太郎',
        ], $overrides);
    }
}
