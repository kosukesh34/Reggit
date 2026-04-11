<?php

namespace App\Http\Controllers\Diagnostics;

use App\Http\Controllers\Controller;
use App\UseCases\Diagnostics\ClientDebugLogUseCase;
use Illuminate\Http\Request;

class ClientDebugController extends Controller
{
    private ClientDebugLogUseCase $clientDebugLogUseCase;

    public function __construct(ClientDebugLogUseCase $clientDebugLogUseCase)
    {
        $this->clientDebugLogUseCase = $clientDebugLogUseCase;
    }

    public function __invoke(Request $request)
    {
        if (! app()->environment(['local', 'testing'])) {
            $expectedToken = trim((string) env('CLIENT_DEBUG_TOKEN', ''));
            $providedToken = trim((string) $request->header('X-Client-Debug-Token', ''));
            if ($expectedToken === '' || $providedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
                abort(404);
            }
        }

        $this->clientDebugLogUseCase->handle($request);

        return response()->json(['ok' => true]);
    }
}
