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

            if (str_starts_with($path, 'api/smaregi/')) {
                return Limit::none()->by('smaregi:'.$request->ip());
            }

            // The global API limiter runs before route authentication. Client-supplied
            // identity and project headers must never partition its quota.
            return Limit::perMinute(240)->by('ip:'.$request->ip());
        });

        // 管理画面ログイン: 5回/分まで（ブルートフォース対策）
        RateLimiter::for('admin-login', function (Request $request) {
            $key = $request->input('login_id', '').'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        // 公開オンボーディング: 10回/分まで
        RateLimiter::for('onboarding', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('analysis-read', function (Request $request) {
            return Limit::perMinute(30)->by('analysis-read:'.$request->attributes->get('apiToken')?->token_hash.':'.$request->path());
        });

        RateLimiter::for('analysis-delivery', function (Request $request) {
            return Limit::perMinute(2)->by('analysis-delivery:'.$request->attributes->get('apiToken')?->token_hash);
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
