<?php

namespace App\UseCases\Liff;

use App\Support\NativeApp;
use Illuminate\Http\Response;

/** LIFF トップ画面のレスポンスを組み立てる。 */
class LiffIndexUseCase
{
    public function handle(): Response
    {
        $viewName = self::resolveViewName();

        return response()
            ->view($viewName)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /** User-Agent からネイティブアプリか LINE LIFF かを判定し、対応するビュー名を返す。 */
    public static function resolveViewName(): string
    {
        $userAgent = request()->header('User-Agent', '');
        $isNativeApp = $userAgent && preg_match(NativeApp::userAgentPattern(), $userAgent);

        return $isNativeApp ? 'frontend.native' : 'frontend.line';
    }
}
