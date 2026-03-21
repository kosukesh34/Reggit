<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\MemberService;
use App\Services\UserService;
use App\UseCases\Member\MemberUpdateUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MemberUpdateController extends Controller
{
    public function __construct(
        private MemberUpdateUseCase $updateUseCase
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $user = app(UserService::class)->getOrCreateUser($request);
            $member = $user->member;
            $projectId = $member ? (int) $member->project_id : (int) ($user->project_id ?? 1);
            $rules = app(MemberService::class)->getProfileValidationRules($projectId);
            $validated = $request->validate($rules);

            $dto = $this->updateUseCase->handle($request, $validated);

            return response()->json($dto->toArray());
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            LogHelper::error('[Member] update failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
