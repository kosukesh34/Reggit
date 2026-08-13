<?php

namespace App\UseCases\Liff;

use App\Support\NativeApp;
use App\UseCases\Liff\Data\LiffIndexInput;
use Illuminate\Http\Response;

/** LIFF トップ画面のレスポンスを組み立てる。 */
class LiffIndexUseCase
{
    public function handle(LiffIndexInput $input): Response
    {
        $viewName = $this->resolveViewName($input->userAgent);

        return response()
            ->view($viewName)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function resolveViewName(string $userAgent): string
    {
        $isNativeApp = $userAgent !== '' && preg_match(NativeApp::userAgentPattern(), $userAgent) === 1;

        return $isNativeApp ? 'frontend.native' : 'frontend.line';
    }
}
