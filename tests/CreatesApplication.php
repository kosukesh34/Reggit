<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Tests\Concerns\InteractsWithTestDatabase;

trait CreatesApplication
{
    use InteractsWithTestDatabase;

    public function createApplication(): Application
    {
        $this->forceTestDatabaseEnvironment();

        $app = require __DIR__.'/../bootstrap/app.php';

        // テスト時に storage 配下が無いと view コンパイルや file セッションで例外になるため作成する
        foreach (['framework/views', 'framework/sessions', 'framework/cache', 'framework/cache/data'] as $relative) {
            $path = $app->storagePath($relative);
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }

        $app->make(Kernel::class)->bootstrap();

        // テストのログを開発・本番と同じ storage/logs に残さない（Docker の env_file の LOG_CHANNEL より優先する）
        $app['config']->set('logging.default', 'null');

        return $app;
    }
}
