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
        private ProjectAppLinkRepository $appLinkRepository,
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

        $appLinks = $this->appLinkRepository->find((int) $project->id);

        return view('admin.projects.edit', ['appLinks' => $appLinks, 'view' => new ProjectFormView(
            $project,
            [
                'line_liff_id' => $this->shopSettingRepository->getLineLiffId((int) $project->id),
                'line_liff_url' => $this->shopSettingRepository->getLineLiffUrl((int) $project->id),
                'line_friend_add_url' => (string) ($this->shopSettingRepository->getValue('line_friend_add_url', '', (int) $project->id) ?? ''),
                // 値は画面に出さず、登録済みかどうかだけ渡す
                'has_line_channel_access_token' => trim((string) $this->shopSettingRepository->getValue('line_channel_access_token', '', (int) $project->id)) !== '' ? '1' : '',
                'has_line_channel_secret' => trim((string) $this->shopSettingRepository->getValue('line_channel_secret', '', (int) $project->id)) !== '' ? '1' : '',
                'liff_entry_url' => url('/liff/'.$project->project_key),
                'line_webhook_url' => url('/api/line/webhook'),
                'frontend_header_text' => (string) ($this->shopSettingRepository->getFrontendHeaderText((int) $project->id) ?? ''),
                'frontend_logo_url' => (string) ($this->shopSettingRepository->getFrontendLogoUrl((int) $project->id) ?? ''),
                'smaregi_contract_id' => (string) ($this->shopSettingRepository->getValue('smaregi_contract_id', '', (int) $project->id) ?? ''),
                'smaregi_client_id' => (string) ($this->shopSettingRepository->getValue('smaregi_client_id', '', (int) $project->id) ?? ''),
                'smaregi_client_secret' => '',
            ],
        )]);
    }
}
