<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Settings\Repositories\ProjectSettings;
use App\Http\Controllers\Controller;
use App\Repositories\Eloquent\ProjectAppLinkRepository;
use App\UseCases\Admin\Project\Data\ProjectFormView;
use App\UseCases\Admin\Project\ProjectEditUseCase;
use App\UseCases\Admin\Project\ProjectIndexUseCase;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectIndexUseCase $indexUseCase,
        private ProjectEditUseCase $editUseCase,
        private ProjectSettings $shopSettingRepository,
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
            ],
        )]);
    }

    public function edit(int $id)
    {
        $project = $this->editUseCase->handle($id);

        $appLinks = app(ProjectAppLinkRepository::class)->find((int) $project->id);

        return view('admin.projects.edit', ['appLinks' => $appLinks, 'view' => new ProjectFormView(
            $project,
            [
                'line_liff_id' => $this->shopSettingRepository->getLineLiffId((int) $project->id),
                'line_liff_url' => $this->shopSettingRepository->getLineLiffUrl((int) $project->id),
                'frontend_header_text' => (string) ($this->shopSettingRepository->getFrontendHeaderText((int) $project->id) ?? ''),
                'frontend_logo_url' => (string) ($this->shopSettingRepository->getFrontendLogoUrl((int) $project->id) ?? ''),
                'smaregi_contract_id' => (string) ($this->shopSettingRepository->getValue('smaregi_contract_id', '', (int) $project->id) ?? ''),
                'smaregi_client_id' => (string) ($this->shopSettingRepository->getValue('smaregi_client_id', '', (int) $project->id) ?? ''),
                'smaregi_client_secret' => '',
            ],
        )]);
    }
}
