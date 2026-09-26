<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

class LiffLegacyRedirectController extends Controller
{
    public function __invoke(string $project_key, string $legacy_state)
    {
        parse_str($legacy_state, $params);
        $queueToken = is_array($params) ? ($params['queue_token'] ?? null) : null;
        $queueId = is_array($params) ? ($params['queue_id'] ?? null) : null;

        Log::info('[LIFF] legacy queue url redirected', [
            'project_key' => $project_key,
            'queue_token' => $queueToken,
            'queue_id' => $queueId,
        ]);

        $query = array_filter([
            'queue_token' => is_string($queueToken) ? $queueToken : null,
            'queue_id' => is_string($queueId) ? $queueId : null,
        ], fn ($value) => $value !== null && $value !== '');

        return redirect()->to('/liff/'.$project_key.($query === [] ? '' : '?'.http_build_query($query)));
    }
}
