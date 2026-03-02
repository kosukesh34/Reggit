<?php

namespace App\Http\Controllers\Smaregi;

use App\Http\Controllers\Controller;
use App\UseCases\Smaregi\SmaregiWebhookHandleUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SmaregiWebhookController extends Controller
{
    public function __construct(
        private SmaregiWebhookHandleUseCase $webhookHandleUseCase
    ) {}

    public function __invoke(Request $request): Response
    {
        $result = $this->webhookHandleUseCase->handle($request);

        return response('', $result['status']);
    }
}
