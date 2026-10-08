<?php

namespace App\Http\Controllers\Diagnostics;

use App\Http\Controllers\Controller;
use App\Http\Support\ClientDebugLogInputFactory;
use App\UseCases\Diagnostics\ClientDebugLogUseCase;
use Illuminate\Http\Request;

class ClientDebugController extends Controller
{
    public function __construct(
        private ClientDebugLogUseCase $clientDebugLogUseCase,
        private ClientDebugLogInputFactory $inputFactory,
    ) {}

    public function __invoke(Request $request)
    {
        if (! (bool) config('app.client_debug_enabled', false)) {
            abort(404);
        }

        $this->clientDebugLogUseCase->handle($this->inputFactory->fromRequest($request));

        return response()->json(['ok' => true]);
    }
}
