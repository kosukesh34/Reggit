<?php

namespace Tests\Unit\Services\Smaregi;

use App\Services\Smaregi\SmaregiListResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SmaregiListResponseTest extends TestCase
{
    #[Test]
    public function returns_numeric_list_as_is(): void
    {
        $rows = [['id' => 1], ['id' => 2]];

        $this->assertSame($rows, SmaregiListResponse::extract($rows, 'products'));
    }

    #[Test]
    public function extracts_named_list_key(): void
    {
        $result = ['products' => [['id' => 9]], 'meta' => ['total' => 1]];

        $this->assertSame([['id' => 9]], SmaregiListResponse::extract($result, 'products'));
    }

    #[Test]
    public function missing_key_returns_empty_array(): void
    {
        $this->assertSame([], SmaregiListResponse::extract(['meta' => []], 'products'));
    }
}
