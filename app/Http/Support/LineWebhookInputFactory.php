<?php

namespace App\Http\Support;

use App\UseCases\Line\Data\LineWebhookInput;
use Illuminate\Http\Request;

class LineWebhookInputFactory
{
    public function fromRequest(Request $request): LineWebhookInput
    {
        $signature = $request->header('X-Line-Signature');
        if (! is_string($signature) || trim($signature) === '') {
            $signature = null;
        }

        return new LineWebhookInput(
            $request->isMethod('GET'),
            $signature,
            $request->getContent(),
        );
    }
}
