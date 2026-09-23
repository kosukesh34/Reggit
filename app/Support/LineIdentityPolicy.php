<?php

namespace App\Support;

final class LineIdentityPolicy
{
    public static function requiresVerifiedToken(): bool
    {
        // A local test setting must never turn an asserted LINE user ID into
        // authentication in a deployed application.
        return app()->environment('production') || config('line.verify_liff_id_token') === true;
    }
}
