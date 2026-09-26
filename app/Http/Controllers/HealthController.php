<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            DB::select('select 1');
        } catch (\Throwable $exception) {
            if ($request->query('deep') === '1') {
                return response()->json(['status' => 'error', 'db' => false], 503);
            }

            return response('error', 503, ['Content-Type' => 'text/plain']);
        }

        if ($request->query('deep') === '1') {
            return response()->json(['status' => 'ok', 'db' => true]);
        }

        return response('ok', 200, ['Content-Type' => 'text/plain']);
    }
}
