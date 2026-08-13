<?php

namespace Tests\Unit\Support;

use App\Support\LiffDesignerSanitizer;
use PHPUnit\Framework\TestCase;

class LiffDesignerSanitizerAppCustomizationTest extends TestCase
{
    public function test_app_customizationがグローバル設定に保持されること(): void
    {
        $result = LiffDesignerSanitizer::sanitizeTabSettings([
            '_global' => [
                'app_display_name' => 'Demo Cafe',
                'app_customization' => [
                    'designPreset' => 'editorial',
                    'colors' => ['primary' => '#112233'],
                    'home' => ['layout' => 'wallet'],
                    'header' => ['shortcut' => 'membership'],
                    'navigation' => [
                        'tabs' => [
                            ['id' => 'home', 'type' => 'home', 'label' => 'ホーム', 'enabled' => true],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('Demo Cafe', $result['_global']['app_display_name']);
        $this->assertIsArray($result['_global']['app_customization']);
        $this->assertSame('editorial', $result['_global']['app_customization']['designPreset']);
        $this->assertSame('#112233', $result['_global']['app_customization']['colors']['primary']);
        $this->assertSame('wallet', $result['_global']['app_customization']['home']['layout']);
        $this->assertSame('membership', $result['_global']['app_customization']['header']['shortcut']);
    }

    public function test_不正なapp_customizationは捨てられること(): void
    {
        $result = LiffDesignerSanitizer::sanitizeTabSettings([
            '_global' => [
                'app_customization' => 'not-an-array',
            ],
        ]);

        $this->assertArrayNotHasKey('app_customization', $result['_global'] ?? []);
    }
}
