<?php

namespace App\UseCases\Liff;

use App\Support\NativeApp;
use Illuminate\Http\Response;

class LiffIndexUseCase
{
    public function handle(): Response
    {
        return response()
            ->view($this->resolveViewName())
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function resolveViewName(): string
    {
        $userAgent = request()->header('User-Agent', '');
        $isNativeApp = $userAgent !== '' && preg_match(NativeApp::userAgentPattern(), $userAgent) === 1;

        return $isNativeApp ? 'frontend.native' : 'frontend.line';
    }
}
