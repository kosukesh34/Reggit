<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Member\MemberPointCardUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class MemberPointCardController extends BaseApiController
{
    public function __construct(
        private MemberPointCardUseCase $pointCardUseCase
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $result = $this->pointCardUseCase->handle($request);
            if ($result->requiresMembership) {
                return $this->error('会員登録が必要です', 404);
            }

            return $this->success(['card' => $result->data]);
        } catch (\Exception $e) {
            LogHelper::error('[Member] point card failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return $this->error('ポイント情報の取得に失敗しました', 500);
        }
    }
}
