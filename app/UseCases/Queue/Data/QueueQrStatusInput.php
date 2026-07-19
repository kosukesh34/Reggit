<?php

namespace App\UseCases\Queue\Data;

use App\Models\User;

/**
 * QR 連携ステータス UseCase への入力。不変。
 */
class QueueQrStatusInput
{
    public function __construct(
        public readonly string $token,
        public readonly int $projectId,
        public readonly string $lineUserId,
        public readonly ?User $contextUser,
        public readonly ?string $deviceIdentifier = null,
        public readonly ?string $devicePlatform = null,
        public readonly ?string $deviceOsVersion = null,
        public readonly ?string $deviceAppVersion = null,
    ) {}
}
