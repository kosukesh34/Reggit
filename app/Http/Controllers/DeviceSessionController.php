<?php

namespace App\Http\Controllers;

use App\UseCases\Device\GetDeviceSessionUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 端末に紐づく会員（line_user_id）を返す。ネイティブアプリ起動時に __LINE_USER_ID__ を復元するために使用する。
 */
class DeviceSessionController extends Controller
{
    public function __construct(
        private GetDeviceSessionUseCase $getDeviceSessionUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $deviceIdentifier = $request->header('X-Device-Identifier');
        $devicePrefix = is_string($deviceIdentifier) && trim($deviceIdentifier) !== ''
            ? substr(trim($deviceIdentifier), 0, 8)
            : null;

        if ($devicePrefix === null) {
            LogHelper::info('[DeviceSession] missing device identifier');

            return response()->json(['line_user_id' => null]);
        }

        $output = $this->getDeviceSessionUseCase->handle($projectId, $deviceIdentifier);

        if ($output->lineUserId === null) {
            LogHelper::info('[DeviceSession] line user not restored', [
                'device_prefix' => $devicePrefix,
            ]);

            return response()->json(['line_user_id' => null]);
        }

        LogHelper::info('[DeviceSession] restored line user', [
            'device_prefix' => $devicePrefix,
            'line_user_tail' => substr($output->lineUserId, -4),
        ]);

        return response()->json([
            'line_user_id' => $output->lineUserId,
        ]);
    }
}
