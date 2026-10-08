<?php

namespace App\Http\Controllers\Diagnostics;

use App\Http\Controllers\Controller;
use App\Http\Support\ClientDebugLogInputFactory;
use App\UseCases\Diagnostics\ClientDebugLogUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

        $configuredToken = (string) config('app.client_debug_token', '');
        $providedToken = (string) $request->bearerToken();
        if ($configuredToken === '' || $providedToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            abort(401);
        }

        Validator::make($request->all(), [
            'event' => ['required', 'string', 'max:100'],
            'project_key' => ['nullable', 'string', 'max:100'],
            'path' => ['nullable', 'string', 'max:2048'],
            'ua' => ['nullable', 'string', 'max:512'],
            'ts' => ['nullable', 'string', 'max:64'],
            'payload' => ['nullable', 'string', 'max:4096'],
        ])->validate();

        $this->clientDebugLogUseCase->handle($this->inputFactory->fromRequest($request));

        return response()->json(['ok' => true]);
    }
}
