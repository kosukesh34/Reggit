<?php

namespace App\View\Composers;

use App\Domain\Settings\Repositories\AdminAppearanceSettings;
use App\Models\Project;
use Illuminate\View\View;

final class AdminLayoutComposer
{
    public function __construct(private readonly AdminAppearanceSettings $settings) {}

    public function compose(View $view): void
    {
        if (! auth('admin')->check()) {
            return;
        }

        $adminUser = auth('admin')->user();
        $isSystemAdmin = $adminUser->isSystemAdmin();
        $allowedIds = $adminUser->getAllowedProjectIds();
        $projects = $isSystemAdmin
            ? Project::where('is_active', true)->orderBy('name')->get()
            : Project::whereIn('id', $allowedIds)->where('is_active', true)->orderBy('name')->get();
        $sessionProjectId = (int) session('admin_project_id');

        if ($sessionProjectId && $adminUser->canAccessProject($sessionProjectId)) {
            $currentProjectId = $sessionProjectId;
        } elseif ($projects->isNotEmpty()) {
            $currentProjectId = (int) $projects->first()->id;
        } else {
            $currentProjectId = $adminUser->project_id ? (int) $adminUser->project_id : 0;
        }

        $view->with(compact('adminUser', 'currentProjectId', 'projects', 'isSystemAdmin'));

        try {
            $view->with('adminSidebarColor', $this->settings->getAdminSidebarColor($currentProjectId));
            $view->with('adminHeaderColor', $this->settings->getAdminHeaderColor($currentProjectId));
            $view->with('adminPrimaryColor', $this->settings->getAdminPrimaryColor($currentProjectId));
        } catch (\Exception $exception) {
            report($exception);
            $view->with('adminSidebarColor', null);
            $view->with('adminHeaderColor', null);
            $view->with('adminPrimaryColor', null);
        }
    }
}
