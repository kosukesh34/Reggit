<?php

namespace App\Http\Controllers;

use App\UseCases\Member\ConfirmExistingMemberLinkUseCase;
use Illuminate\View\View;
use InvalidArgumentException;

class ExistingMemberLinkConfirmController extends Controller
{
    public function __construct(
        private ConfirmExistingMemberLinkUseCase $confirmUseCase,
    ) {}

    public function __invoke(string $token): View
    {
        try {
            $this->confirmUseCase->handle($token);
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
