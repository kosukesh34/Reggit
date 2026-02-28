<?php

namespace App\UseCases\Liff;

use Illuminate\Http\Response;

/** LIFF トップ画面のレスポンスを組み立てる。 */
class LiffIndexUseCase
{
    public function handle(): Response
    {
        return response()
            ->view('index')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
