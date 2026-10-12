<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Project;
use App\Models\Settings\ProjectLineLiffSetting;
use Illuminate\Http\Request;

class PaymentReturnController extends Controller
{
    public function __invoke(Request $request)
    {
        $status = $request->query('status') === 'success' ? 'success' : 'cancel';
        $orderId = max(0, (int) $request->query('order_id'));

        if ($request->query('surface') === 'web') {
            $project = Project::where('project_key', $request->query('project_key'))->firstOrFail();
            $order = Order::withoutGlobalScopes()->where('project_id', $project->id)->findOrFail($orderId);

            $liffId = ProjectLineLiffSetting::find($project->id)?->line_liff_id;
            $returnUrl = filled($liffId) ? 'https://liff.line.me/'.rawurlencode($liffId) : url('/liff/'.rawurlencode($project->project_key));
            $returnUrl .= '?payment_order_id='.$order->id.'&payment_status='.$status.'#/menu';

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
