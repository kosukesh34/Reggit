<?php

namespace App\Http\Controllers;

use App\Services\Health\DatabaseHealthCheck;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HealthController extends Controller
{
    public function __construct(private DatabaseHealthCheck $databaseHealthCheck) {}

    public function __invoke(Request $request): Response
    {
        $databaseIsHealthy = $this->databaseHealthCheck->isHealthy();

        if ($request->query('deep') === '1') {
            return response()->json([
                'status' => $databaseIsHealthy ? 'ok' : 'error',
                'db' => $databaseIsHealthy,
            ], $databaseIsHealthy ? 200 : 503);
        }

        return response(
            $databaseIsHealthy ? 'ok' : 'error',
            $databaseIsHealthy ? 200 : 503,
            ['Content-Type' => 'text/plain'],
        );
    }
}
