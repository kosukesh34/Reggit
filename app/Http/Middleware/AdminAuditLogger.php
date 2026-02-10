<?php

namespace App\Http\Middleware;

use App\Models\AdminAuditLog;
use App\Util\AdminContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuditLogger
{
    private const WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];
    private const SENSITIVE_KEYS = ['password', 'password_confirmation'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!in_array($request->getMethod(), self::WRITE_METHODS, true)) {
            return $response;
        }

        $admin = $request->user('admin');
        if (!$admin) {
            return $response;
        }

        $payload = $request->all();
        foreach (self::SENSITIVE_KEYS as $key) {
            if (array_key_exists($key, $payload)) {
                $payload[$key] = '***';
            }
        }

        $projectId = AdminContext::getCurrentProjectId();
        $projectId = (is_int($projectId) || $projectId === null) ? $projectId : null;

        $route = $request->route();
        $routeName = $route ? $route->getName() : null;
        $url = $request->fullUrl();
        if (strlen($url) > 500) {
            $url = substr($url, 0, 497) . '...';
        }

        AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'project_id' => $projectId,
            'route_name' => $routeName !== null ? (string) $routeName : null,
            'method' => $request->getMethod(),
            'url' => $url,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'payload' => $payload,
        ]);

        return $response;
    }
}
