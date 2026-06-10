<?php

namespace App\Http\Controllers;

use App\UseCases\Line\Data\LineWebhookInput;
use App\UseCases\Line\LineWebhookHandleUseCase;
use Illuminate\Http\Request;

class LineWebhookController extends Controller
{
    public function __construct(
        private LineWebhookHandleUseCase $handleUseCase
    ) {}

    public function handle(Request $request)
    {
        $result = $this->handleUseCase->handle(LineWebhookInput::fromRequest($request));

        return response()->json($result['payload'], $result['status']);
    }
}
