<?php

namespace App\Http\Controllers;

use App\Domain\Survey\Exceptions\InvalidSurveyAnswers;
use App\Domain\Survey\Exceptions\SurveyClosed;
use App\Http\Requests\SurveyAnswerRequest;
use App\Services\UserService;
use App\UseCases\Survey\Data\SurveyView;
use App\UseCases\Survey\ListOpenSurveysUseCase;
use App\UseCases\Survey\ShowSurveyUseCase;
use App\UseCases\Survey\SubmitSurveyAnswerUseCase;
use App\Util\AdminContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** 会員アプリのアンケート一覧・回答。 */
class SurveyController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function index(Request $request, ListOpenSurveysUseCase $useCase): JsonResponse
    {
        $surveys = $useCase->handle(AdminContext::requireCurrentProjectId(), $this->memberId($request));

        return response()->json(['surveys' => array_map(fn (SurveyView $survey) => $survey->toArray(), $surveys)]);
    }

    public function show(Request $request, int $id, ShowSurveyUseCase $useCase): JsonResponse
    {
        $survey = $useCase->handle(AdminContext::requireCurrentProjectId(), $id, $this->memberId($request));
        abort_if($survey === null, 404);

        return response()->json(['survey' => $survey->toArray()]);
    }

    public function answer(SurveyAnswerRequest $request, int $id, SubmitSurveyAnswerUseCase $useCase): JsonResponse
    {
        try {
            $granted = $useCase->handle($this->userService->getOrCreateUser($request), $id, $request->answers());
        } catch (InvalidSurveyAnswers $e) {
            throw ValidationException::withMessages($e->errors);
        } catch (SurveyClosed $e) {
            abort(410, $e->getMessage());
        }

        return response()->json(['success' => true, 'data' => ['granted_points' => $granted]]);
    }

    private function memberId(Request $request): ?int
    {
        $member = $this->userService->findUser($request)?->member;

        return $member !== null ? (int) $member->id : null;
    }
}
