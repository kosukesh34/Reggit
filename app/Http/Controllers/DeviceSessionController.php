<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDevice;
use App\Util\AdminContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 端末に紐づく会員（line_user_id）を返す。ネイティブアプリ起動時に __LINE_USER_ID__ を復元するために使用する。
 */
class DeviceSessionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $deviceIdentifier = $request->header('X-Device-Identifier');
        if (! is_string($deviceIdentifier) || trim($deviceIdentifier) === '') {
            return response()->json(['line_user_id' => null]);
        }
        $deviceIdentifier = trim($deviceIdentifier);

        $device = UserDevice::where('project_id', $projectId)
            ->where('device_identifier', $deviceIdentifier)
            ->orderByDesc('last_used_at')
            ->first();

        if (! $device) {
            return response()->json(['line_user_id' => null]);
        }

        $user = User::where('id', $device->user_id)->where('project_id', $projectId)->first();
        if (! $user || ! $user->line_user_id) {
            return response()->json(['line_user_id' => null]);
        }

        // ゲスト用の仮 ID は返さない（会員として紐づいている場合のみ復元）
        if (str_starts_with($user->line_user_id, 'guest-')) {
            return response()->json(['line_user_id' => null]);
        }

        return response()->json([
            'line_user_id' => $user->line_user_id,
        ]);
    }
}
