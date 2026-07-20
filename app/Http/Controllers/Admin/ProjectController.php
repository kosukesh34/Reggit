<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectStoreRequest;
use App\Http\Requests\Admin\ProjectUpdateRequest;
use App\Models\ShopSetting;
use App\UseCases\Admin\Project\ProjectEditUseCase;
use App\UseCases\Admin\Project\ProjectIndexUseCase;
use App\UseCases\Admin\Project\ProjectStoreUseCase;
use App\UseCases\Admin\Project\ProjectUpdateUseCase;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectIndexUseCase $indexUseCase,
        private ProjectStoreUseCase $storeUseCase,
        private ProjectEditUseCase $editUseCase,
        private ProjectUpdateUseCase $updateUseCase
    ) {}

    public function index()
    {
        $projects = $this->indexUseCase->handle();

        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        return view('admin.projects.create', [
            'project' => null,
            'projectSettings' => [
                'line_liff_id' => trim((string) config('line.liff_id', '')),
                'line_liff_url' => trim((string) config('line.liff_url', '')),
                'frontend_header_text' => '',
                'frontend_logo_url' => '',
                'business_hours_start' => '10:00',
                'business_hours_end' => '22:00',
                'line_primary_color' => '#000000',
                'line_primary_dark' => '#333333',
                'line_success_color' => '#000000',
                'line_danger_color' => '#dc3545',
            ],
        ]);
    }

    public function store(ProjectStoreRequest $request)
    {
        $this->storeUseCase->handle(
            $request->toProjectPayload(),
            $request->toProjectSettingsPayload()
        );

        return redirect()->route('system-admin.projects.index')->with('success', 'プロジェクトを作成しました');
    }

    public function edit(int $id)
    {
        $project = $this->editUseCase->handle($id);

        return view('admin.projects.edit', [
            'project' => $project,
            'projectSettings' => [
                'line_liff_id' => ShopSetting::getLineLiffId((int) $project->id),
                'line_liff_url' => ShopSetting::getLineLiffUrl((int) $project->id),
                'frontend_header_text' => (string) (ShopSetting::getFrontendHeaderText((int) $project->id) ?? ''),
                'frontend_logo_url' => (string) (ShopSetting::getFrontendLogoUrl((int) $project->id) ?? ''),
                'business_hours_start' => (string) ShopSetting::getValue('business_hours_start', '10:00', (int) $project->id),
                'business_hours_end' => (string) ShopSetting::getValue('business_hours_end', '22:00', (int) $project->id),
                'line_primary_color' => (string) ShopSetting::getValue('line_primary_color', '#000000', (int) $project->id),
                'line_primary_dark' => (string) ShopSetting::getValue('line_primary_dark', '#333333', (int) $project->id),
                'line_success_color' => (string) ShopSetting::getValue('line_success_color', '#000000', (int) $project->id),
                'line_danger_color' => (string) ShopSetting::getValue('line_danger_color', '#dc3545', (int) $project->id),
                'smaregi_contract_id' => (string) (ShopSetting::getValue('smaregi_contract_id', '', (int) $project->id) ?? ''),
                'smaregi_client_id' => (string) (ShopSetting::getValue('smaregi_client_id', '', (int) $project->id) ?? ''),
                'smaregi_client_secret' => '',
            ],
            'isOnboarding' => false,
            'showAdminAccount' => false,
            'requireSmaregi' => false,
        ]);
    }

    public function update(ProjectUpdateRequest $request, int $id)
    {
        $this->updateUseCase->handle(
            $id,
            $request->toProjectPayload(),
            $request->toProjectSettingsPayload()
        );

        return redirect()->route('system-admin.projects.index')->with('success', 'プロジェクトを更新しました');
    }
}
