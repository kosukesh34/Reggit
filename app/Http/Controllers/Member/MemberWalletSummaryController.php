<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\UseCases\Member\MemberWalletSummaryUseCase;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use RuntimeException;

class MemberWalletSummaryController extends Controller
{
    public function __construct(
        private MemberWalletSummaryUseCase $walletSummaryUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $data = $this->walletSummaryUseCase->handle($this->userService->getOrCreateUser($request));

            return response()->json($data);
        } catch (RuntimeException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? (int) $e->getCode() : 400;

            return response()->json(['message' => $e->getMessage()], $status);
        } catch (\Exception $e) {
            LogHelper::error('[Member] wallet summary failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'サマリーの取得に失敗しました'], 500);
        }
    }
}
