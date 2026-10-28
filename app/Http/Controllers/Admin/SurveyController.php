<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Survey\Exceptions\SurveyWithoutQuestions;
use App\Http\Requests\Admin\SurveyRequest;
use App\UseCases\Admin\Survey\AdminSurveyCreateUseCase;
use App\UseCases\Admin\Survey\AdminSurveyDestroyUseCase;
use App\UseCases\Admin\Survey\AdminSurveyDuplicateUseCase;
use App\UseCases\Admin\Survey\AdminSurveyEditUseCase;
use App\UseCases\Admin\Survey\AdminSurveyExportUseCase;
use App\UseCases\Admin\Survey\AdminSurveyIndexUseCase;
use App\UseCases\Admin\Survey\AdminSurveyShowUseCase;
use App\UseCases\Admin\Survey\AdminSurveyStoreUseCase;
use App\UseCases\Admin\Survey\AdminSurveyUpdateUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** アンケート（NPS・選択式・自由記述）の作成と集計。 */
class SurveyController extends AdminBaseController
{
    public function index(AdminSurveyIndexUseCase $useCase): View
    {
        return view('admin.surveys.index', ['surveys' => $useCase->handle($this->resolveAdminProjectId())]);
    }

    public function create(Request $request, AdminSurveyCreateUseCase $useCase): View
    {
        $template = $request->query('template');

        return view('admin.surveys.form', ['view' => $useCase->handle($this->resolveAdminProjectId(), is_string($template) ? $template : null)]);
    }

    public function duplicate(string $projectKey, int $id, AdminSurveyDuplicateUseCase $useCase): RedirectResponse
    {
        $copyId = $useCase->handle($this->resolveAdminProjectId(), $id);

        return redirect()->route('admin.surveys.edit', [...$this->buildAdminRouteParams(), 'id' => $copyId])
            ->with('success', 'アンケートを複製しました。内容を確認して「回答を受け付ける」をオンにしてください');
    }

    public function store(SurveyRequest $request, AdminSurveyStoreUseCase $useCase): RedirectResponse
    {
        try {
            $id = $useCase->handle($this->resolveAdminProjectId(), $request->toInput());
        } catch (SurveyWithoutQuestions $e) {
            throw ValidationException::withMessages(['questions' => $e->getMessage()]);
        }

        return redirect()->route('admin.surveys.show', [...$this->buildAdminRouteParams(), 'id' => $id])
            ->with('success', 'アンケートを作成しました');
    }

    public function show(string $projectKey, int $id, AdminSurveyShowUseCase $useCase): View
    {
        return view('admin.surveys.show', ['view' => $useCase->handle($this->resolveAdminProjectId(), $this->resolveAdminProjectKey(), $id)]);
    }

    public function export(string $projectKey, int $id, AdminSurveyExportUseCase $useCase): StreamedResponse
    {
        $export = $useCase->handle($this->resolveAdminProjectId(), $id);

        return response()->streamDownload(function () use ($export): void {
            $out = fopen('php://output', 'w');
            // Excel で文字化けしないよう UTF-8 の BOM を付ける
            fwrite($out, "\u{FEFF}");
            foreach ($export->rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $export->filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function edit(string $projectKey, int $id, AdminSurveyEditUseCase $useCase): View
    {
        return view('admin.surveys.form', ['view' => $useCase->handle($this->resolveAdminProjectId(), $id)]);
    }

    public function update(SurveyRequest $request, string $projectKey, int $id, AdminSurveyUpdateUseCase $useCase): RedirectResponse
    {
        try {
            $useCase->handle($this->resolveAdminProjectId(), $id, $request->toInput());
        } catch (SurveyWithoutQuestions $e) {
            throw ValidationException::withMessages(['questions' => $e->getMessage()]);
        }

        return redirect()->route('admin.surveys.show', [...$this->buildAdminRouteParams(), 'id' => $id])
            ->with('success', 'アンケートを更新しました');
    }

    public function destroy(string $projectKey, int $id, AdminSurveyDestroyUseCase $useCase): RedirectResponse
    {
        $useCase->handle($this->resolveAdminProjectId(), $id);

        return redirect()->route('admin.surveys.index', $this->buildAdminRouteParams())
            ->with('success', 'アンケートを削除しました');
    }
}
