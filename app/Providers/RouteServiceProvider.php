<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/home';

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $path = trim((string) $request->path(), '/');
            if ($path === 'api/client-debug') {
                return Limit::perMinute(600)->by($request->ip());
            }

            $deviceIdentifier = trim((string) $request->header('X-Device-Identifier', ''));
            $lineUserId = trim((string) $request->header('X-Line-User-Id', ''));
            $projectKey = trim((string) $request->header('X-Project-Key', ''));
            $key = $request->user()?->id
                ?: ($lineUserId !== '' ? "line:{$lineUserId}" : null)
                ?: ($deviceIdentifier !== '' ? "device:{$deviceIdentifier}" : null)
                ?: "ip:{$request->ip()}";
            if ($projectKey !== '') {
                $key .= "|project:{$projectKey}";
            }

            return Limit::perMinute(240)->by($key);
        });

        // 管理画面ログイン: 5回/分まで（ブルートフォース対策）
        RateLimiter::for('admin-login', function (Request $request) {
            $key = $request->input('login_id', '').'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
