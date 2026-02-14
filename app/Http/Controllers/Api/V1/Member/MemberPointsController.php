<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Member\MemberPointsUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class MemberPointsController extends BaseApiController
{
    public function __construct(
        private MemberPointsUseCase $pointsUseCase
    ) {
    }

    public function __invoke(Request $request)
    {
        try {
            $result = $this->pointsUseCase->handle($request);
            if ($result->hasMember === false) {
                return $this->error('会員登録が必要です', 404);
            }

            return $this->success([
                'points' => $result->points,
                'transactions' => $result->transactions,
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Member] points failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);
            return $this->error('ポイント情報の取得に失敗しました', 500);
        }
    }
}
