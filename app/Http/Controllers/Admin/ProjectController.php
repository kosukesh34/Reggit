<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Http\Controllers\Controller;
use App\UseCases\Admin\Project\Data\ProjectFormView;
use App\UseCases\Admin\Project\ProjectEditUseCase;
use App\UseCases\Admin\Project\ProjectIndexUseCase;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectIndexUseCase $indexUseCase,
        private ProjectEditUseCase $editUseCase,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function index()
    {
        $projects = $this->indexUseCase->handle();

        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        return view('admin.projects.create', ['view' => new ProjectFormView(
            null,
            [
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
        )]);
    }

    public function edit(int $id)
    {
        $project = $this->editUseCase->handle($id);

        return view('admin.projects.edit', ['view' => new ProjectFormView(
            $project,
            [
                'line_liff_id' => $this->shopSettingRepository->getLineLiffId((int) $project->id),
                'line_liff_url' => $this->shopSettingRepository->getLineLiffUrl((int) $project->id),
                'frontend_header_text' => (string) ($this->shopSettingRepository->getFrontendHeaderText((int) $project->id) ?? ''),
                'frontend_logo_url' => (string) ($this->shopSettingRepository->getFrontendLogoUrl((int) $project->id) ?? ''),
                'business_hours_start' => (string) $this->shopSettingRepository->getValue('business_hours_start', '10:00', (int) $project->id),
                'business_hours_end' => (string) $this->shopSettingRepository->getValue('business_hours_end', '22:00', (int) $project->id),
                'line_primary_color' => (string) $this->shopSettingRepository->getValue('line_primary_color', '#000000', (int) $project->id),
                'line_primary_dark' => (string) $this->shopSettingRepository->getValue('line_primary_dark', '#333333', (int) $project->id),
                'line_success_color' => (string) $this->shopSettingRepository->getValue('line_success_color', '#000000', (int) $project->id),
                'line_danger_color' => (string) $this->shopSettingRepository->getValue('line_danger_color', '#dc3545', (int) $project->id),
                'smaregi_contract_id' => (string) ($this->shopSettingRepository->getValue('smaregi_contract_id', '', (int) $project->id) ?? ''),
                'smaregi_client_id' => (string) ($this->shopSettingRepository->getValue('smaregi_client_id', '', (int) $project->id) ?? ''),
                'smaregi_client_secret' => '',
            ],
        )]);
    }
}
