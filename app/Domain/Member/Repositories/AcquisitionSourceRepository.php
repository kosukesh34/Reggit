<?php

namespace App\Domain\Member\Repositories;

/** 会員登録の流入経路（経路QRの code）。 */
interface AcquisitionSourceRepository
{
    /** @return array{id: int, tag_name: string}|null */
    public function findByCode(int $projectId, string $code): ?array;

    /** 登録元が未記録の会員にだけ記録する。記録したら true */
    public function attachIfMissing(int $memberId, int $sourceId): bool;
}
