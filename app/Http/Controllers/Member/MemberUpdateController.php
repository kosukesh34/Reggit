<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberProfileRequest;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberUpdateInput;
use App\UseCases\Member\MemberUpdateUseCase;
use App\Util\LogHelper;
use Illuminate\Validation\ValidationException;

class MemberUpdateController extends Controller
{
    public function __construct(
        private MemberUpdateUseCase $updateUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(MemberProfileRequest $request)
    {
        try {
            $dto = $this->updateUseCase->handle(new MemberUpdateInput(
                $this->userService->getOrCreateUser($request),
                $request->validated()
            ));

            return response()->json($dto->toArray());
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            LogHelper::error('[Member] update failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
