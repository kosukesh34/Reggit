<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class WinbackRetirementTest extends TestCase
{
    public function test_winback_cannot_be_invoked_or_scheduled(): void
    {
        $this->assertArrayNotHasKey('crm:winback', Artisan::all());

        foreach (app(Schedule::class)->events() as $event) {
            $this->assertStringNotContainsString('crm:winback', $event->command ?? '');
        }
    }
}
