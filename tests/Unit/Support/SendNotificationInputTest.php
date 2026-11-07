<?php

namespace Tests\Unit\Support;

use App\Support\SendNotificationInput;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendNotificationInputTest extends TestCase
{
    #[Test]
    public function missing_key_defaults_to_true(): void
    {
        $this->assertTrue(SendNotificationInput::fromValidated([]));
    }

    #[Test]
    public function accepts_true_one_and_string_one(): void
    {
        $this->assertTrue(SendNotificationInput::fromValidated(['send_notification' => true]));
        $this->assertTrue(SendNotificationInput::fromValidated(['send_notification' => 1]));
        $this->assertTrue(SendNotificationInput::fromValidated(['send_notification' => '1']));
    }

    #[Test]
    public function rejects_falsey_values(): void
    {
        $this->assertFalse(SendNotificationInput::fromValidated(['send_notification' => false]));
        $this->assertFalse(SendNotificationInput::fromValidated(['send_notification' => 0]));
        $this->assertFalse(SendNotificationInput::fromValidated(['send_notification' => '0']));
    }
}
