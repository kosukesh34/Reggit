<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\UseCases\Payment\PaymentReturnUrlUseCase;
use Illuminate\Http\Request;

class PaymentReturnController extends Controller
{
    public function __invoke(Request $request, PaymentReturnUrlUseCase $returnUrls)
    {
        $status = $request->query('status') === 'success' ? 'success' : 'cancel';
        $orderId = max(0, (int) $request->query('order_id'));

        if ($request->query('surface') === 'web') {
            $returnUrl = $returnUrls->forWeb(
                (string) $request->query('project_key'),
                $status,
                $orderId,
                $request->query('subscription') === '1',
                $request->getHost(),
            );

            return response()->view('payment.return', [
                'returnUrl' => $returnUrl, 'status' => $status,
            ])->header('Cache-Control', 'private, no-store');
        }

        abort_if($orderId < 1, 400, '注文番号が不正です。');

        return response()->view('payment.return', [
            'returnUrl' => "reggit://payment?status={$status}&order_id={$orderId}",
            'status' => $status,
        ])->header('Cache-Control', 'private, no-store');
    }
}
