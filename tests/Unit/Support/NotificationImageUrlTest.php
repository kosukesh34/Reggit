<?php

namespace Tests\Unit\Support;

use App\Support\NotificationImageUrl;
use Tests\TestCase;

class NotificationImageUrlTest extends TestCase
{
    public function test_for_push_相対パスを_app_url_で絶対化する(): void
    {
        config(['app.url' => 'https://example.com']);

        $this->assertSame(
            'https://example.com/storage/coupon.png',
            NotificationImageUrl::forPush('/storage/coupon.png')
        );
    }

    public function test_for_line_httpやlocalhostはnullを返す(): void
    {
        config(['app.url' => 'http://localhost:8080']);

        $this->assertNull(NotificationImageUrl::forLine('http://localhost:8080/storage/a.png'));
        $this->assertNull(NotificationImageUrl::forLine('/storage/a.png'));
    }

    public function test_for_line_公開httpsはそのまま返す(): void
    {
        $this->assertSame(
            'https://cdn.example.com/a.png',
            NotificationImageUrl::forLine('https://cdn.example.com/a.png')
        );
    }

    public function test_for_line_ngrokにはスキップクエリを付ける(): void
    {
        $this->assertSame(
            'https://abc.ngrok-free.app/storage/a.png?ngrok-skip-browser-warning=1',
            NotificationImageUrl::forLine('https://abc.ngrok-free.app/storage/a.png')
        );
    }

    public function test_for_line_overrideがあるとき旧storage_urlを差し替える(): void
    {
        config([
            'app.url' => 'https://app.example.com',
            'line.notification_image_base_url' => 'https://abc.ngrok-free.app',
        ]);

        $this->assertSame(
            'https://abc.ngrok-free.app/storage/a.png?ngrok-skip-browser-warning=1',
            NotificationImageUrl::forLine('https://old.ngrok-free.app/storage/a.png')
        );
    }

    public function test_for_line_overrideなしでは外部cdnを書き換えない(): void
    {
        config([
            'app.url' => 'https://app.example.com',
            'line.notification_image_base_url' => '',
        ]);

        $this->assertSame(
            'https://cdn.example.com/a.png',
            NotificationImageUrl::forLine('https://cdn.example.com/a.png')
        );
    }
}
