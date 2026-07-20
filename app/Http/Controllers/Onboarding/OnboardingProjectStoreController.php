<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\OnboardingStoreRequest;
use App\UseCases\Onboarding\Data\OnboardingStoreInput;
use App\UseCases\Onboarding\OnboardingStoreUseCase;
use Illuminate\Http\JsonResponse;

class OnboardingProjectStoreController extends Controller
{
    public function __invoke(
        OnboardingStoreRequest $request,
        OnboardingStoreUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->handle(new OnboardingStoreInput(
            $request->toProjectPayload(),
            $request->toSettingsPayload(),
            $request->toAdminPayload(),
        ));

        return response()->json($output->toArray(), 201);
    }
}
