<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentReturnController extends Controller
{
    public function __invoke(Request $request)
    {
        $status = $request->query('status') === 'success' ? 'success' : 'cancel';
        $orderId = max(0, (int) $request->query('order_id'));

        return redirect()->away("reggit://payment?status={$status}&order_id={$orderId}");
    }
}
