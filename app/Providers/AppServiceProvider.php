<?php

namespace App\Providers;

use App\Models\ShopSetting;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        if (request()->isSecure() ||
            request()->header('X-Forwarded-Proto') === 'https' ||
            request()->header('X-Forwarded-Ssl') === 'on') {
            URL::forceScheme('https');
        }

        View::composer('index', function ($view) {
            try {
                $view->with('lineTheme', ShopSetting::getLineThemeColors());
            } catch (\Throwable $e) {
                $view->with('lineTheme', [
                    'primary' => '#000000',
                    'primary_rgb' => '0, 0, 0',
                    'primary_dark' => '#333333',
                    'success' => '#000000',
                    'danger' => '#dc3545',
                ]);
            }
        });
    }
}

