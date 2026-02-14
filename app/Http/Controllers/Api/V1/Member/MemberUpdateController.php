<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Member\MemberUpdateUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class MemberUpdateController extends BaseApiController
{
    public function __construct(
        private MemberUpdateUseCase $updateUseCase
    ) {
    }

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'birthday' => 'nullable|date',
            'address' => 'nullable|string|max:500',
        ]);

        try {
            $dto = $this->updateUseCase->handle($request, $validated);
            return $this->success(['member' => $dto->toArray()], '更新しました');
        } catch (\Exception $e) {
            LogHelper::error('[Member] update failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);
            return $this->error($e->getMessage(), 400);
        }
    }
}
