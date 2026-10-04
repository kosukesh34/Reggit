<?php

namespace App\UseCases\Member;

use App\Models\PointGrantCode;
use App\Models\Project;
use App\Support\ProjectQrAppUrl;
use Carbon\CarbonImmutable;

class OpenPointQrUseCase
{
    public function handle(Project $project, string $token): string
    {
        $code = PointGrantCode::query()->where('project_id', $project->id)->where('token', $token)->firstOrFail();
        abort_if($code->expires_at->lte(CarbonImmutable::now()), 410, 'このQRの有効期限が切れています。');

        return ProjectQrAppUrl::tabUrl($project, 'member', ['point_code' => $token]);
    }
}
