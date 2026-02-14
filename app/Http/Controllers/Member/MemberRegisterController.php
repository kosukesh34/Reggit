<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\UseCases\Member\MemberRegisterUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class MemberRegisterController extends Controller
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
            $status = $result->alreadyRegistered ? 200 : 201;
            return response()->json($result->member->toArray(), $status);
        } catch (\Exception $e) {
            LogHelper::error('[Member] register failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
