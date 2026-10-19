<?php

namespace App\Support;

use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Request;

/** パスキー関連操作の監査ログ（表示用 event 付き）。 */
final class AdminPasskeyAudit
{
    /**
     * @param  array{
     *     passkey_id?: int|string|null,
     *     passkey_name?: string|null,
     * }  $payload
     */
    public static function record(
        AdminUser $admin,
        string $event,
        array $payload = [],
        ?string $routeName = null,
        string $method = 'POST',
    ): void {
        $safe = ['event' => $event];
        if (array_key_exists('passkey_id', $payload) && $payload['passkey_id'] !== null && $payload['passkey_id'] !== '') {
            $safe['passkey_id'] = $payload['passkey_id'];
        }
        if (array_key_exists('passkey_name', $payload) && is_string($payload['passkey_name']) && $payload['passkey_name'] !== '') {
            $safe['passkey_name'] = $payload['passkey_name'];
        }

        AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'project_id' => $admin->project_id,
            'route_name' => $routeName,
            'method' => $method,
            'url' => Request::path(),
            'ip' => null,
            'user_agent' => null,
            'payload' => $safe,
        ]);
    }
}
