<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectAppLinkRequest;
use App\UseCases\Admin\Project\UpdateProjectAppLinksUseCase;
use Illuminate\Http\RedirectResponse;

class ProjectAppLinkController extends Controller
{
    public function update(ProjectAppLinkRequest $request, int $id, UpdateProjectAppLinksUseCase $useCase): RedirectResponse
    {
        $useCase->handle($id, $request->urls());

        return redirect()->route('system-admin.projects.edit', $id)->with('success', 'アプリのダウンロードURLを保存しました');
    }
}
