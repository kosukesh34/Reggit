<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberProfileImageRequest;
use App\Services\UserService;
use App\UseCases\Member\MemberProfileImageUseCase;
use Illuminate\Http\Request;

class MemberProfileImageController extends Controller
{
    public function __construct(
        private MemberProfileImageUseCase $useCase,
        private UserService $userService,
    ) {}

    public function store(MemberProfileImageRequest $request)
    {
        return response()->json($this->useCase->store(
            $this->userService->getOrCreateUser($request),
            $request->imageFile(),
        )->toArray());
    }

    public function destroy(Request $request)
    {
        return response()->json($this->useCase->destroy(
            $this->userService->getOrCreateUser($request),
        )->toArray());
    }
}
