<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\UseCases\Payment\ProjectPaymentGatewayResolver;
use App\Util\AdminContext;

class PaymentMethodsController extends Controller
{
    public function __invoke(ProjectPaymentGatewayResolver $resolver)
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $methods = $resolver->methods($projectId);
        $key = $resolver->publishableKey($projectId);

        return response()->json([
            'methods' => $methods,
            'test_mode' => array_diff($methods, ['cash']) !== [] && str_starts_with($key, 'pk_test_'),
        ])
            ->header('Cache-Control', 'private, no-store');
    }
}
