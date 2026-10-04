<?php

namespace App\UseCases\Admin\Qr;

use App\Domain\Settings\Repositories\OrderSettings;
use App\Models\PointGrantCode;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class IssuePointGrantQrUseCase
{
    public function __construct(private readonly OrderSettings $settings) {}

    public function handle(int $projectId, int $points, int $expiresMinutes, int $quota): PointGrantCode
    {
        if (! $this->settings->getUsePoints($projectId)) {
            throw ValidationException::withMessages(['points' => 'ポイント機能は利用できません。']);
        }

        return PointGrantCode::query()->create([
            'project_id' => $projectId,
            'token' => bin2hex(random_bytes(24)),
            'points' => $points,
            'usage_quota' => $quota,
            'expires_at' => CarbonImmutable::now()->addMinutes($expiresMinutes),
        ]);
    }
}
