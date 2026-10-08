<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Tests\CreatesApplication;

class LiffAssetsTest extends TestCase
{
    use CreatesApplication;

    public function test_external_line_browser_uses_build_assets_with_local_hot_server(): void
    {
        $this->assertAssets('https://shop.ngrok-free.app/liff/default', 'http://0.0.0.0:5173', false);
    }

    public function test_local_browser_keeps_hot_reload(): void
    {
        $this->assertAssets('http://localhost/liff/default', 'http://0.0.0.0:5173', true);
    }

    public function test_public_hot_server_is_preserved(): void
    {
        $this->assertAssets('https://shop.example.com/liff/default', 'https://vite.example.com', true);
    }

    private function assertAssets(string $url, string $hotUrl, bool $hot): void
    {
        $hotFile = tempnam(sys_get_temp_dir(), 'liff-hot-');
        file_put_contents($hotFile, $hotUrl);
        try {
            $vite = $this->app->make(Vite::class)->useHotFile($hotFile);
            $this->app->instance('request', Request::create($url));
            $html = view('frontend.partials._line-app-assets')->render();
            if ($hot) {
                $this->assertStringContainsString($hotUrl.'/@vite/client', $html);
            } else {
                $this->assertStringNotContainsString($hotUrl, $html);
                $this->assertStringContainsString('/build/assets/', $html);
            }
            $this->assertSame($hotFile, $vite->hotFile());
        } finally {
            unlink($hotFile);
        }
    }
}
