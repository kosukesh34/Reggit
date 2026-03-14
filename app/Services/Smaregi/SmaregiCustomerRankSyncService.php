<?php

namespace App\Services\Smaregi;

use App\Util\StrHelper;
use Illuminate\Support\Facades\Log;

class SmaregiCustomerRankSyncService
{
    private const RANK_NAME_MAX_LENGTH = 85;

    private const FETCH_RANKS_LIMIT = 200;

    private const RESPONSE_KEY_RANKS = 'customer_ranks';

    private const RESPONSE_KEY_RANKS_ALT = 'customerRanks';

    private SmaregiApiService $smaregiApi;

    public function __construct(SmaregiApiService $smaregiApi)
    {
        $this->smaregiApi = $smaregiApi;
    }

    /**
     * @param  array<int, array{key: string, label: string, min_points: int, is_active: bool, ...}>  $rows
     */
    public function sync(int $projectId, array $rows): void
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if (StrHelper::isEmptyStringOrNull($contractId)) {
            return;
        }

        $existing = $this->fetchExistingRanks($contractId);
        $appKeys = array_column($rows, 'key');

        foreach ($rows as $row) {
            $key = isset($row['key']) ? (string) $row['key'] : '';
            if (StrHelper::isEmptyStringOrNull($key)) {
                continue;
            }
            $label = isset($row['label']) ? (string) $row['label'] : $key;
            $body = ['rankName' => mb_substr($label, 0, self::RANK_NAME_MAX_LENGTH)];
            if (isset($existing[$key])) {
                $this->updateRank($contractId, $key, $body);
            } else {
                $this->createRank($contractId, $key, $body);
            }
        }

        $this->deleteRemovedRanks($contractId, $appKeys, $existing, $projectId);
    }

    public function hasSmaregiContract(int $projectId): bool
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);

        return $contractId !== null && $contractId !== '';
    }

    /**
     * @param  array<string, array<string, string|int|float|bool|null>>  $existing  ランクkey => API応答1件
     */
    private function deleteRemovedRanks(string $contractId, array $appKeys, array $existing, int $projectId): void
    {
        foreach (array_keys($existing) as $rankKey) {
            if (in_array($rankKey, $appKeys, true)) {
                continue;
            }
            $res = $this->smaregiApi->deleteCustomerRank($contractId, $rankKey);
            if ($res === null) {
                Log::warning('[Smaregi] customer_rank delete failed or skipped', [
                    'project_id' => $projectId,
                    'rank' => $rankKey,
                ]);
            }
        }
    }

    private function updateRank(string $contractId, string $key, array $body): void
    {
        $res = $this->smaregiApi->updateCustomerRank($contractId, $key, $body);
        if ($res === null) {
            throw new \RuntimeException(
                'スマレジの会員ランク更新に失敗しました（'.$key.'）。ログ(storage/logs/laravel.log)の [Smaregi] を確認してください。'
            );
        }
    }

    private function createRank(string $contractId, string $key, array $body): void
    {
        $body['rank'] = $key;
        $res = $this->smaregiApi->createCustomerRank($contractId, $body);
        if ($res === null) {
            throw new \RuntimeException(
                'スマレジの会員ランク登録に失敗しました（'.$key.'）。ログ(storage/logs/laravel.log)の [Smaregi] を確認してください。'
            );
        }
    }

    /**
     * @return array<string, array<string, string|int|float|bool|null>> ランクkey => API応答1件
     */
    private function fetchExistingRanks(string $contractId): array
    {
        $result = $this->smaregiApi->getCustomerRanks($contractId, ['limit' => self::FETCH_RANKS_LIMIT]);
        if ($result === null) {
            return [];
        }
        $list = $this->extractList($result, self::RESPONSE_KEY_RANKS);
        $out = [];
        foreach ($list as $item) {
            if (! is_array($item)) {
                continue;
            }
            $rank = $item['rank'] ?? $item['rankId'] ?? $item['id'] ?? null;
            if ($rank !== null && $rank !== '') {
                $out[(string) $rank] = $item;
            }
        }

        return $out;
    }

    /**
     * @param  array<int|string, string|int|float|bool|array|null>  $result  API応答
     * @return array<int|string, string|int|float|bool|array|null>
     */
    private function extractList(array $result, string $listKey): array
    {
        if (isset($result[0]) || array_keys($result) === range(0, count($result) - 1)) {
            return $result;
        }
        $list = $result[$listKey] ?? $result[self::RESPONSE_KEY_RANKS_ALT] ?? [];

        return is_array($list) ? $list : [];
    }
}
