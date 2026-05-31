<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDevice;
use App\Util\AdminContext;
use App\Util\LogHelper;
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
            LogHelper::info('[DeviceSession] missing device identifier');

            return response()->json(['line_user_id' => null]);
        }
        $deviceIdentifier = trim($deviceIdentifier);

        $device = UserDevice::where('project_id', $projectId)
            ->where('device_identifier', $deviceIdentifier)
            ->orderByDesc('last_used_at')
            ->first();

        if (! $device) {
            LogHelper::info('[DeviceSession] device not registered', [
                'device_prefix' => substr($deviceIdentifier, 0, 8),
            ]);

            return response()->json(['line_user_id' => null]);
        }

        $user = User::where('id', $device->user_id)->where('project_id', $projectId)->first();
        if (! $user || ! $user->line_user_id) {
            LogHelper::info('[DeviceSession] user not linked', [
                'device_prefix' => substr($deviceIdentifier, 0, 8),
                'user_id' => $device->user_id,
            ]);

            return response()->json(['line_user_id' => null]);
        }

        // ゲスト用の仮 ID は返さない（会員として紐づいている場合のみ復元）
        if (str_starts_with($user->line_user_id, 'guest-')) {
            LogHelper::info('[DeviceSession] guest user skipped', [
                'device_prefix' => substr($deviceIdentifier, 0, 8),
                'user_id' => $user->id,
            ]);

            return response()->json(['line_user_id' => null]);
        }

        LogHelper::info('[DeviceSession] restored line user', [
            'device_prefix' => substr($deviceIdentifier, 0, 8),
            'user_id' => $user->id,
            'line_user_tail' => substr($user->line_user_id, -4),
        ]);

        return response()->json([
            'line_user_id' => $user->line_user_id,
        ]);
    }
}
