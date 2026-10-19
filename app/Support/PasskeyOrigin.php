<?php

namespace App\Support;

use Illuminate\Http\Request;

/** WebAuthn の RP ID / origin を、実際に開いているホストに合わせる。 */
final class PasskeyOrigin
{
    public static function apply(?Request $request = null): void
    {
        $request ??= request();
        $host = $request->getHost();
        if ($host === '' || $host === '0.0.0.0') {
            $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        }

        $forced = config('passkeys.relying_party_id_force');
        $rpId = is_string($forced) && $forced !== '' ? $forced : $host;

        $origins = array_values(array_unique(array_filter([
            $request->getSchemeAndHttpHost(),
            rtrim((string) config('app.url'), '/'),
            ...array_map(
                static fn (mixed $origin): string => is_string($origin) ? rtrim($origin, '/') : '',
                (array) config('passkeys.allowed_origins', []),
            ),
        ])));

        config([
            'passkeys.relying_party_id' => $rpId,
            'passkeys.allowed_origins' => $origins !== [] ? $origins : [rtrim((string) config('app.url'), '/')],
        ]);
    }
}
