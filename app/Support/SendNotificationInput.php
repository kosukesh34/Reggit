<?php

namespace App\Support;

final class SendNotificationInput
{
    /**
     * @param  array<string, string|string[]|int|bool|null>  $validated
     */
    public static function fromValidated(array $validated, string $key = 'send_notification'): bool
    {
        if (! array_key_exists($key, $validated)) {
            return true;
        }
        $value = $validated[$key];

        return $value === true || $value === 1 || $value === '1';
    }
}
