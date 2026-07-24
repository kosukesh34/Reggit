<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\UseCases\Lottery\MemberLotteryIndexUseCase;
use App\UseCases\Lottery\NotifyLotteryWinUseCase;
use App\UseCases\Lottery\PlayLotteryEntitlementUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use RuntimeException;

class MemberLotteryController extends Controller
{
    public function __construct(
        private MemberLotteryIndexUseCase $indexUseCase,
        private PlayLotteryEntitlementUseCase $playUseCase,
        private NotifyLotteryWinUseCase $notifyWinUseCase,
        private UserService $userService,
    ) {}

    public function index(Request $request)
    {
        try {
            $data = $this->indexUseCase->handle($this->userService->getOrCreateUser($request));

            return response()->json($data);
        } catch (RuntimeException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? (int) $e->getCode() : 400;

            return response()->json(['message' => $e->getMessage()], $status);
        } catch (\Exception $e) {
            LogHelper::error('[Lottery] index failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'ガチャ情報の取得に失敗しました'], 500);
        }
    }

    public function play(Request $request, int $id)
    {
        try {
            $data = $this->playUseCase->handle($this->userService->getOrCreateUser($request), $id);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (RuntimeException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? (int) $e->getCode() : 400;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $status);
        } catch (\Exception $e) {
            LogHelper::error('[Lottery] play failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'entitlement_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'プレイに失敗しました',
            ], 500);
        }
    }

    public function notifyWin(Request $request, int $id)
    {
        try {
            $this->notifyWinUseCase->handle($this->userService->getOrCreateUser($request), $id);

            return response()->json(['success' => true]);
        } catch (RuntimeException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? (int) $e->getCode() : 400;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $status);
        } catch (\Exception $e) {
            LogHelper::error('[Lottery] notifyWin failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'entitlement_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '通知の送信に失敗しました',
            ], 500);
        }
    }
}
