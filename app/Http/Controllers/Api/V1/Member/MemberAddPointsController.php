<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Member\MemberAddPointsUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class MemberAddPointsController extends BaseApiController
{
    public function __construct(
        private MemberAddPointsUseCase $addPointsUseCase
    ) {
    }

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        try {
            $result = $this->addPointsUseCase->handle(
                $request,
                (int) $validated['points'],
                $validated['description'] ?? null
            );
            if ($result->hasMember === false) {
                return $this->error('会員登録が必要です', 404);
            }

            return $this->success([
                'points' => $result->points,
            ], 'ポイントが追加されました');
        } catch (\Exception $e) {
            LogHelper::error('[Member] add points failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);
            return $this->error('ポイントの追加に失敗しました', 500);
        }
    }
}
