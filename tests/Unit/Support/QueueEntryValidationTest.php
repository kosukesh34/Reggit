<?php

namespace Tests\Unit\Support;

use App\Support\QueueEntryValidation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QueueEntryValidationTest extends TestCase
{
    #[Test]
    public function store_rules_match_member_app_contract(): void
    {
        $this->assertSame([
            'party_size' => 'required|integer|min:1|max:20',
            'guest_name' => 'nullable|string|max:255',
            'guest_phone' => 'nullable|string|max:50',
        ], QueueEntryValidation::storeRules());
    }
}
