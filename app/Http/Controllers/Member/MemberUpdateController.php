<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\UseCases\Member\MemberUpdateUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class MemberUpdateController extends Controller
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
            return response()->json($dto->toArray());
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
