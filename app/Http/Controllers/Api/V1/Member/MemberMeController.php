<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Member\MemberMeUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class MemberMeController extends BaseApiController
{
    public function __construct(
        private MemberMeUseCase $meUseCase
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $dto = $this->meUseCase->handle($request);

            return $this->success(['member' => $dto->toArray()]);
        } catch (\Exception $e) {
            LogHelper::error('[Member] me failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return $this->error('認証が必要です', 401);
        }
    }
}
