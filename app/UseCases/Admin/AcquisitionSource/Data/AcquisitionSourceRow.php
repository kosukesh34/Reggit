<?php

namespace App\UseCases\Admin\AcquisitionSource\Data;

/** 流入経路一覧の1行。QRに載せるURLを含む。 */
final class AcquisitionSourceRow
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $code,
        public readonly string $url,
        /** QRで開く画面の名前 */
        public readonly string $landingLabel,
        public readonly int $membersCount,
        public readonly int $recentMembersCount,
    ) {}
}
