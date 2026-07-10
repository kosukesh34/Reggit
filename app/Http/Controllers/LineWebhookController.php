<?php

namespace App\Http\Controllers;

use App\Http\Support\LineWebhookInputFactory;
use App\UseCases\Line\LineWebhookHandleUseCase;
use Illuminate\Http\Request;

class LineWebhookController extends Controller
{
    public function __construct(
        private LineWebhookHandleUseCase $handleUseCase,
        private LineWebhookInputFactory $inputFactory,
    ) {}

    public function handle(Request $request)
    {
        $result = $this->handleUseCase->handle($this->inputFactory->fromRequest($request));

        return response()->json($result['payload'], $result['status']);
    }
}
