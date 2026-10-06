<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\UseCases\Member\ConfirmExistingMemberLinkUseCase;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ExistingMemberLinkConfirmController extends Controller
{
    public function __construct(
        private ConfirmExistingMemberLinkUseCase $confirmUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(string $token): View
    {
        try {
            $this->confirmUseCase->pendingRequest($token);
        } catch (InvalidArgumentException $e) {
            return view('existing-member-link.result', ['message' => $e->getMessage(), 'isSuccess' => false]);
        }

        return view('existing-member-link.confirm', ['token' => $token]);
    }

    public function confirm(Request $request, string $token): View
    {
        try {
            $pending = $this->confirmUseCase->pendingRequest($token);
            $user = $pending->user_id !== null ? $this->userService->findUser($request) : null;
            $this->confirmUseCase->handle($token, $user);
        } catch (InvalidArgumentException $e) {
            return view('existing-member-link.result', [
                'message' => $e->getMessage(),
                'isSuccess' => false,
            ]);
        }

        return view('existing-member-link.result', [
            'message' => '既存会員を連携しました。アプリを開き直してください。',
            'isSuccess' => true,
        ]);
    }
}
