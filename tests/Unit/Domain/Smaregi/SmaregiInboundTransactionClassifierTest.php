<?php

namespace Tests\Unit\Domain\Smaregi;

use App\Domain\Smaregi\Services\SmaregiInboundTransactionClassifier;
use App\Enums\SmaregiCancelDivision;
use App\Enums\SmaregiInboundTransactionKind;
use App\Enums\SmaregiReturnSalesDivision;
use App\Enums\SmaregiTransactionHeadDivision;
use PHPUnit\Framework\TestCase;

class SmaregiInboundTransactionClassifierTest extends TestCase
{
    public function test_通常販売をsaleに分類すること(): void
    {
        $result = (new SmaregiInboundTransactionClassifier)->classify([
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => '259',
            'total' => '2046',
            'terminalTranDateTime' => '2026-07-19T01:44:25+09:00',
            'details' => [['productName' => 'ケーキ', 'quantity' => '1']],
        ]);

        $this->assertSame(SmaregiInboundTransactionKind::SALE, $result->kind());
        $this->assertSame('259', $result->customerId());
        $this->assertSame(2046.0, $result->signedAmount());
        $this->assertFalse($result->hasSmaregiManagedPointDelta());
        $this->assertCount(1, $result->details());
    }

    public function test_返品をreturnに分類し金額が負になること(): void
    {
        $result = (new SmaregiInboundTransactionClassifier)->classify([
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'returnSales' => SmaregiReturnSalesDivision::RETURN,
            'customerId' => '259',
            'total' => '1000',
            'terminalTranDateTime' => '2026-07-19T02:00:00+09:00',
            'newPoint' => '-10',
        ]);

        $this->assertTrue($result->isReturn());
        $this->assertSame(-1000.0, $result->signedAmount());
        $this->assertSame(1000.0, $result->absAmount());
        $this->assertTrue($result->hasSmaregiManagedPointDelta());
    }

    public function test_取消をcancelledに分類すること(): void
    {
        $result = (new SmaregiInboundTransactionClassifier)->classify([
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::CANCELLED,
            'customerId' => '259',
            'total' => '2046',
        ]);

        $this->assertTrue($result->isCancelled());
    }

    public function test_ポイント加算取引はskipになること(): void
    {
        $result = (new SmaregiInboundTransactionClassifier)->classify([
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::POINT_ADD,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => '263',
            'totalPoint' => '2000',
        ]);

        $this->assertTrue($result->isSkip());
        $this->assertSame(
            SmaregiInboundTransactionClassifier::SKIP_REASON_NOT_NORMAL_SALE,
            $result->skipReason()
        );
    }
}
