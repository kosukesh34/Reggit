<?php

namespace App\UseCases\Stamp;

use App\Models\Project;
use App\Models\Scopes\ProjectScope;
use App\Models\StampGrantCode;
use App\Support\LiffTabUrlBuilder;
use App\UseCases\Stamp\Data\StampGrantOpenOutput;

class StampGrantOpenUseCase
{
    public function __construct(private LiffTabUrlBuilder $liffTabUrls) {}

    public function handle(string $token): StampGrantOpenOutput
    {
        $code = StampGrantCode::withoutGlobalScope(ProjectScope::class)->where('token', $token)->first();
        if ($code === null) {
            return new StampGrantOpenOutput(false, 404, '付与QRが見つかりません', null);
        }
        if ($code->isExpired()) {
            return new StampGrantOpenOutput(false, 410, 'このQRの有効期限が切れています', null);
        }

        $project = Project::query()->find($code->project_id);
        if ($project === null || trim((string) $project->project_key) === '') {
            return new StampGrantOpenOutput(false, 404, '付与QRが見つかりません', null);
        }

        return new StampGrantOpenOutput(
            true,
            302,
            '',
            $this->liffTabUrls->build((int) $project->id, (string) $project->project_key, 'stamps', [
                'g' => $code->token,
            ]),
        );
    }
}
