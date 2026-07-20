<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class OnboardingPageController extends Controller
{
    public function __invoke(): View
    {
        return view('onboarding.create', [
            'project' => null,
            'projectSettings' => [
                'smaregi_contract_id' => '',
                'smaregi_client_id' => '',
                'smaregi_client_secret' => '',
            ],
            'isOnboarding' => true,
            'showAdminAccount' => true,
            'requireSmaregi' => true,
        ]);
    }
}
