<?php

namespace App\Services\Smaregi;

use App\Util\StrHelper;
use Illuminate\Support\Facades\Log;

class SmaregiCustomerRankSyncService
{
    /** スマレジ API の pointGivingUnitPrice 上限 */
    public const POINT_GIVING_UNIT_PRICE_MAX = 99999;

    public const POINT_GIVING_UNIT = '1';

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
     * @param  array<int, array{key: string, label: string, min_points: int, is_active: bool, order?: int, point_earn_rate?: int|null, smaregi_rank_id?: string|null, ...}>  $rows
     * @return array<string, string> ローカル key => スマレジ rank ID
     */
    public function sync(int $projectId, array $rows): array
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if (StrHelper::isEmptyStringOrNull($contractId)) {
            return [];
        }

        $existing = $this->fetchExistingRanks($contractId);
        $existingByName = $this->indexByName($existing);
        $usedRankIds = [];
        $rankIdsByKey = [];

        foreach ($rows as $row) {
            $key = isset($row['key']) ? (string) $row['key'] : '';
            if (StrHelper::isEmptyStringOrNull($key)) {
                continue;
            }
            $label = isset($row['label']) ? (string) $row['label'] : $key;
            $name = mb_substr($label, 0, self::RANK_NAME_MAX_LENGTH);
            $body = $this->buildRequestBody($name, $row);
            $savedRankId = isset($row['smaregi_rank_id']) ? trim((string) $row['smaregi_rank_id']) : '';

            if ($savedRankId !== '' && isset($existing[$savedRankId])) {
                $this->updateRank($contractId, $savedRankId, $body, $key);
                $usedRankIds[] = $savedRankId;
                $rankIdsByKey[$key] = $savedRankId;
                $oldName = isset($existing[$savedRankId]['name']) ? (string) $existing[$savedRankId]['name'] : '';
                if ($oldName !== '') {
                    unset($existingByName[$oldName]);
                }

                continue;
            }

            if (isset($existingByName[$name])) {
                $rankId = $existingByName[$name];
                $this->updateRank($contractId, $rankId, $body, $key);
                $usedRankIds[] = $rankId;
                $rankIdsByKey[$key] = $rankId;
                unset($existingByName[$name]);

                continue;
            }

            $createdRankId = $this->createRank($contractId, $key, $body);
            $usedRankIds[] = $createdRankId;
            $rankIdsByKey[$key] = $createdRankId;
        }

        $this->deleteRemovedRanks($contractId, $usedRankIds, $existing, $projectId);

        return $rankIdsByKey;
    }

    public function hasSmaregiContract(int $projectId): bool
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);

        return $contractId !== null && $contractId !== '';
    }

    /**
     * @param  array{key?: string, label?: string, min_points?: int, is_active?: bool, order?: int, point_earn_rate?: int|null, ...}  $row
     * @return array{name: string, displaySequence: string, displayFlag: string, pointGivingUnitPrice: string, pointGivingUnit: string}
     */
    private function buildRequestBody(string $name, array $row): array
    {
        $isActive = $row['is_active'] ?? true;
        $order = (int) ($row['order'] ?? 1);
        $pointEarnRate = (int) ($row['point_earn_rate'] ?? 0);
        $unitPrice = max(1, min(self::POINT_GIVING_UNIT_PRICE_MAX, $pointEarnRate > 0 ? $pointEarnRate : 100));

        return [
            'name' => $name,
            'displaySequence' => (string) max(1, min(999, $order > 0 ? $order : 1)),
            'displayFlag' => ($isActive === true || $isActive === 1 || $isActive === '1') ? '1' : '0',
            'pointGivingUnitPrice' => (string) $unitPrice,
            'pointGivingUnit' => self::POINT_GIVING_UNIT,
        ];
    }

    /**
     * @param  array<string, array<string, string|int|float|bool|null>>  $existing  rankId => API応答1件
     * @return array<string, string> name => rankId
     */
    private function indexByName(array $existing): array
    {
        $byName = [];
        foreach ($existing as $rankId => $item) {
            $name = isset($item['name']) ? (string) $item['name'] : '';
            if ($name === '') {
                continue;
            }
            $byName[$name] = (string) $rankId;
        }

        return $byName;
    }

    /**
     * @param  array<int, string>  $usedRankIds
     * @param  array<string, array<string, string|int|float|bool|null>>  $existing
     */
    private function deleteRemovedRanks(string $contractId, array $usedRankIds, array $existing, int $projectId): void
    {
        foreach (array_keys($existing) as $rankId) {
            if (in_array((string) $rankId, $usedRankIds, true)) {
                continue;
            }
            $res = $this->smaregiApi->deleteCustomerRank($contractId, (string) $rankId);
            if ($res === null) {
                Log::warning('[Smaregi] customer_rank delete failed or skipped', [
                    'project_id' => $projectId,
                    'rank' => $rankId,
                ]);
                throw new \RuntimeException('スマレジの会員ランク削除に失敗しました（'.$rankId.'）。');
            }
        }
    }

    /**
     * @param  array{name: string, displaySequence: string, displayFlag: string, pointGivingUnitPrice: string, pointGivingUnit: string}  $body
     */
    private function updateRank(string $contractId, string $rankId, array $body, string $key): void
    {
        $res = $this->smaregiApi->updateCustomerRank($contractId, $rankId, $body);
        if ($res === null) {
            throw new \RuntimeException(
                'スマレジの会員ランク更新に失敗しました（'.$key.'）。ログ(storage/logs/laravel.log)の [Smaregi] を確認してください。'
            );
        }
    }

    /**
     * @param  array{name: string, displaySequence: string, displayFlag: string, pointGivingUnitPrice: string, pointGivingUnit: string}  $body
     */
    private function createRank(string $contractId, string $key, array $body): string
    {
        $res = $this->smaregiApi->createCustomerRank($contractId, $body);
        if ($res === null) {
            throw new \RuntimeException(
                'スマレジの会員ランク登録に失敗しました（'.$key.'）。ログ(storage/logs/laravel.log)の [Smaregi] を確認してください。'
            );
        }
        $rankId = isset($res['rank']) ? (string) $res['rank'] : '';
        if ($rankId === '') {
            throw new \RuntimeException(
                'スマレジの会員ランク登録に失敗しました（'.$key.'）。ログ(storage/logs/laravel.log)の [Smaregi] を確認してください。'
            );
        }

        return $rankId;
    }

    /**
     * @return array<string, array<string, string|int|float|bool|null>> ランクID => API応答1件
     */
    private function fetchExistingRanks(string $contractId): array
    {
        $result = $this->smaregiApi->getCustomerRanks($contractId, ['limit' => self::FETCH_RANKS_LIMIT]);
        if ($result === null) {
            throw new \RuntimeException('スマレジの会員ランク一覧取得に失敗しました。ランクの変更を中止しました。');
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
