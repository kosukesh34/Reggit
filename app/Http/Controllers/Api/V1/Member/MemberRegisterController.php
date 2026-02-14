<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Member\MemberRegisterUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class MemberRegisterController extends BaseApiController
{
    public function __construct(
        private MemberRegisterUseCase $registerUseCase
    ) {
    }

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'birthday' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
        ]);

        try {
            $result = $this->registerUseCase->handle($request, $validated);
            if ($result->alreadyRegistered) {
                return $this->success(['member' => $result->member->toArray()], '既に会員登録済みです');
            }

            return $this->success(['member' => $result->member->toArray()], '会員登録が完了しました', 201);
        } catch (\Exception $e) {
            LogHelper::error('[Member] register failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);
            return $this->error($e->getMessage(), 400);
        }
    }
}
