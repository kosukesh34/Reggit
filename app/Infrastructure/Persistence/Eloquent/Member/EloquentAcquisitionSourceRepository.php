<?php

namespace App\Infrastructure\Persistence\Eloquent\Member;

use App\Domain\Member\Repositories\AcquisitionSourceRepository;
use App\Models\AcquisitionSource;
use App\Models\Member;

final class EloquentAcquisitionSourceRepository implements AcquisitionSourceRepository
{
    public function findByCode(int $projectId, string $code): ?array
    {
        $source = AcquisitionSource::withoutGlobalScopes()->where('project_id', $projectId)->where('code', $code)->first();

        return $source === null ? null : ['id' => (int) $source->id, 'tag_name' => $source->tagName()];
    }

    public function attachIfMissing(int $memberId, int $sourceId): bool
    {
        return Member::withoutGlobalScopes()->whereKey($memberId)->whereNull('acquisition_source_id')
            ->update(['acquisition_source_id' => $sourceId]) === 1;
    }
}
