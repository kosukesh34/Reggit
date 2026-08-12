<?php

namespace App\Http\Controllers;

use App\Models\StampCard;
use App\Services\Admin\MemberAdminService;
use App\Services\UserService;
use App\Util\AdminContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StampGrantController extends Controller
{
    public function __construct(
        private UserService $userService,
        private MemberAdminService $memberAdminService,
    ) {}

    public function __invoke(Request $request, int $stampCard): JsonResponse
    {
        $projectId = AdminContext::getCurrentProjectId();
        if ($projectId === null) {
            return response()->json(['error' => '店舗情報を確認できませんでした'], 404);
        }

        $card = StampCard::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->find($stampCard);
        if ($card === null) {
            return response()->json(['error' => '有効なスタンプカードが見つかりません'], 404);
        }

        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member()->where('project_id', $projectId)->first();
        if ($member === null) {
            return response()->json(['error' => 'スタンプの付与には会員登録が必要です'], 403);
        }

        $nonce = trim((string) $request->query('nonce', ''));
        if ($nonce === '') {
            return response()->json(['error' => '付与QRが正しくありません'], 422);
        }

        $redemptionKey = sprintf('stamp-grant:%d:%s:%d', $projectId, hash('sha256', $nonce), $member->id);
        if (! Cache::add($redemptionKey, true, now()->addHour())) {
            return response()->json(['error' => 'このQRからのスタンプは付与済みです'], 409);
        }

        try {
            $memberStamp = $this->memberAdminService->addStampsToMember(
                $member,
                (int) $card->id,
                1,
                '店舗QRから付与'
            );
        } catch (\Throwable $e) {
            Cache::forget($redemptionKey);
            report($e);

            return response()->json(['error' => 'スタンプを追加できませんでした'], 500);
        }

        return response()->json([
            'message' => 'スタンプを1個追加しました',
            'stamp_card_id' => (int) $card->id,
            'current_stamps' => (int) $memberStamp->current_stamps,
        ]);
    }
}
