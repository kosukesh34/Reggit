<?php

namespace App\Http\Middleware;

use App\Models\AdminAuditLog;
use App\Util\AdminContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class AdminAuditLogger
{
    private const WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /** Only retain operational fields that are safe and useful for auditing. */
    private const SAFE_FIELDS = [
        'status', 'service_status', 'is_active', 'price', 'sort_order',
        'role', 'permissions', 'permission_ids', 'project_id', 'admin_user_id',
        'product_id', 'coupon_id', 'reservation_id', 'order_id', 'store_id',
        'id', 'type', 'action', 'enabled', 'display_order',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->getMethod(), self::WRITE_METHODS, true)) {
            return $response;
        }

        $admin = $request->user('admin');
        if ($admin === null) {
            return $response;
        }

        $payload = $this->safePayload($request->all());

        $projectId = AdminContext::getCurrentProjectId();
        $projectId = (is_int($projectId) || $projectId === null) ? $projectId : null;

        $route = $request->route();
        $routeName = $route ? $route->getName() : null;
        AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'project_id' => $projectId,
            'route_name' => $routeName !== null ? (string) $routeName : null,
            'method' => $request->getMethod(),
            // URLs, IP addresses and user agents can contain identifying data.
            'url' => $request->path(),
            'ip' => null,
            'user_agent' => null,
            'payload' => $payload,
        ]);

        return $response;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function safePayload(array $payload): array
    {
        $safe = [];
        foreach ($payload as $key => $value) {
            if (! in_array((string) $key, self::SAFE_FIELDS, true)) {
                continue;
            }
            if ($value instanceof UploadedFile) {
                continue;
            }
            if (is_array($value)) {
                // Keep only simple values; free text and nested form data may hold PII.
                $value = array_filter($value, fn ($item) => is_scalar($item) || $item === null);
            }
            if (is_scalar($value) || $value === null || is_array($value)) {
                $safe[$key] = $value;
            }
        }

        return $safe;
    }
}
