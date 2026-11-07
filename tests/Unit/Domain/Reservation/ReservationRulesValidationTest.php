<?php

namespace Tests\Unit\Domain\Reservation;

use App\Domain\Reservation\ValueObjects\ReservationRules;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReservationRulesValidationTest extends TestCase
{
    #[Test]
    public function write_validation_rules_cover_member_write_fields(): void
    {
        $rules = ReservationRules::writeValidationRules();

        $this->assertSame('required|date|after:now', $rules['reserved_at']);
        $this->assertStringContainsString((string) ReservationRules::MAX_PARTY_SIZE_LIMIT, $rules['number_of_people']);
        $this->assertArrayHasKey('notes', $rules);
        $this->assertArrayHasKey('reservation_course_id', $rules);
    }
}
