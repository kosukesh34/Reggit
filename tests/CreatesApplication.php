<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        // テスト時に view コンパイル先ディレクトリが無いと InvalidArgumentException になるため作成する
        $viewPath = $app->storagePath('framework/views');
        if (! is_dir($viewPath)) {
            mkdir($viewPath, 0755, true);
        }

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
