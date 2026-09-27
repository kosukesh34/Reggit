<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Models\QueueEntry;
use App\Services\UserService;
use App\Support\QueueRequestContext;
use App\Support\QueueSessionStore;
use App\Util\AdminContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeQueueAccess
{
    public function __construct(
        private UserService $users,
        private QueueSessionStore $sessions,
    ) {}

    public function handle(Request $request, Closure $next, string $mode = 'owner'): Response
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $admin = $request->user('admin');
        if (! $admin instanceof AdminUser && $request->user() instanceof AdminUser) {
            $admin = $request->user();
        }
        $permission = $request->isMethod('GET') ? 'queue.view' : 'queue.update';
        if ($admin !== null && $admin->canAccessProject($projectId)
            && (! $admin->isStaff() || $admin->hasPermission($permission))) {
            return $next($request);
        }
        if ($mode === 'staff') {
            return response()->json(['error' => '店舗端末の認証が必要です'], 403);
        }
        $entry = QueueEntry::where('project_id', $projectId)->find($request->route('id'));
        if ($entry === null) {
            abort(404);
        }
        $user = $this->users->findUser($request);
        $token = QueueRequestContext::queueToken($request);
        $ownsEntry = $user !== null && ((int) $entry->user_id === (int) $user->id
            || ($user->member !== null && (int) $entry->member_id === (int) $user->member->id));
        $holdsToken = $token !== null && is_string($entry->qr_token) && $entry->qr_token !== ''
            && hash_equals($entry->qr_token, $token);
        $holdsSession = $this->sessions->getEntryId($request, $projectId) === (int) $entry->id;
        if (! $ownsEntry && ! $holdsToken && ! $holdsSession) {
            abort(404);
        }

        return $next($request);
    }
}
