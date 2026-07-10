<?php

namespace App\UseCases\Diagnostics;

use App\UseCases\Diagnostics\Data\ClientDebugLogInput;
use Illuminate\Support\Facades\Log;

/**
 * クライアントデバッグログを記録する。
 */
class ClientDebugLogUseCase
{
    public function handle(ClientDebugLogInput $input): void
    {
        Log::info('[ClientDebug] '.$input->event, [
            'project_key' => $input->projectKey,
            'path' => $input->path,
            'ua' => $input->userAgent,
            'ts' => $input->timestamp,
            'payload' => $input->payload,
            'method' => $input->method,
        ]);
    }
}
