<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Member\MemberPointHistoryUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class MemberPointHistoryController extends BaseApiController
{
    public function __construct(
        private MemberPointHistoryUseCase $pointHistoryUseCase
    ) {
    }

    public function __invoke(Request $request)
    {
        try {
            $result = $this->pointHistoryUseCase->handle($request, 20);
            if ($result->requiresMembership || $result->transactions === null) {
                return $this->error('会員登録が必要です', 404);
            }

            $transactions = $result->transactions->getCollection()->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'points' => $transaction->points,
                    'description' => $transaction->description,
                    'order_number' => $transaction->order?->order_number,
                    'created_at' => $transaction->created_at?->toIso8601String(),
                    'expires_at' => $transaction->expires_at?->format('Y-m-d'),
                ];
            });

            return $this->success([
                'transactions' => $transactions,
                'pagination' => [
                    'total' => $result->transactions->total(),
                    'per_page' => $result->transactions->perPage(),
                    'current_page' => $result->transactions->currentPage(),
                    'last_page' => $result->transactions->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Member] point history failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);
            return $this->error('ポイント履歴の取得に失敗しました', 500);
        }
    }
}
