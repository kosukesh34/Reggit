<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\UseCases\Admin\Project\Data\ProjectFormView;
use Illuminate\View\View;

class OnboardingPageController extends Controller
{
    public function __invoke(): View
    {
        return view('onboarding.create', ['view' => new ProjectFormView(
            null,
            [
                'smaregi_contract_id' => '',
                'smaregi_client_id' => '',
                'smaregi_client_secret' => '',
            ],
            true,
            true,
            true,
        )]);
    }
}
