<?php

namespace Tests\Unit\Support;

use App\Support\DailySequenceNumber;
use Carbon\Carbon;
use Tests\TestCase;

class DailySequenceNumberTest extends TestCase
{
    public function test_build_と_displayが日付と通番になること(): void
    {
        $date = Carbon::create(2026, 4, 20);

        $this->assertSame('202604201', DailySequenceNumber::build(1, $date));
        $this->assertSame('2026042020', DailySequenceNumber::build(20, $date));
        $this->assertSame('202604202022', DailySequenceNumber::build(2022, $date));

        $this->assertSame('201', DailySequenceNumber::formatDisplay(1, $date));
        $this->assertSame('2020', DailySequenceNumber::formatDisplay(20, $date));
        $this->assertSame('202022', DailySequenceNumber::formatDisplay(2022, $date));
    }

    public function test_format_stored_for_displayが日と通番を返すこと(): void
    {
        $this->assertSame('201', DailySequenceNumber::formatStoredForDisplay('202604201'));
        $this->assertSame('2020', DailySequenceNumber::formatStoredForDisplay('2026042020'));
        $this->assertSame('202022', DailySequenceNumber::formatStoredForDisplay('202604202022'));
    }

    public function test_parse_seqが通番を取り出せること(): void
    {
        $date = Carbon::create(2026, 4, 20);

        $this->assertSame(1, DailySequenceNumber::parseSeq('202604201', $date));
        $this->assertSame(20, DailySequenceNumber::parseSeq('2026042020', $date));
        $this->assertSame(2022, DailySequenceNumber::parseSeq('202604202022', $date));
    }

    public function test_next_seqは上限なく増えること(): void
    {
        $this->assertSame(1, DailySequenceNumber::nextSeq(0));
        $this->assertSame(2023, DailySequenceNumber::nextSeq(2022));
    }
}
