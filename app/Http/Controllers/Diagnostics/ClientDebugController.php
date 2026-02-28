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
        $this->clientDebugLogUseCase->handle($request);

        return response()->json(['ok' => true]);
    }
}
