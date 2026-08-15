<?php

namespace App\Services\Smaregi;

use App\Util\StrHelper;
use Illuminate\Support\Facades\Log;

/**
 * プロフィール任意項目をスマレジ会員自由項目ラベルへ同期する。
 */
class SmaregiCustomerCustomFieldLabelSyncService
{
    public const LABEL_MAX = 85;

    private const FETCH_LIMIT = 99;

    private SmaregiApiService $smaregiApi;

    public function __construct(SmaregiApiService $smaregiApi)
    {
        $this->smaregiApi = $smaregiApi;
    }

    /**
     * @param  list<array{key: string, label: string, smaregi_no?: int|null}>  $rows
     * @return array<string, int> ローカル key => スマレジ自由項目番号
     */
    public function sync(int $projectId, array $rows): array
    {
        if ($rows === []) {
            return [];
        }
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if (StrHelper::isEmptyStringOrNull($contractId)) {
            return [];
        }

        try {
            $existing = $this->fetchExistingLabels($contractId);
        } catch (\Throwable $e) {
            Log::warning('[Smaregi] custom field label fetch failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
        if ($existing === null) {
            return [];
        }
        $existingByLabel = $this->indexByLabel($existing);
        $usedFieldNumbers = [];
        $fieldNumbersByKey = [];

        try {
            foreach ($rows as $row) {
                $key = (string) ($row['key'] ?? '');
                $label = mb_substr(trim((string) ($row['label'] ?? $key)), 0, self::LABEL_MAX);
                if ($key === '' || $label === '') {
                    continue;
                }
                $fieldNumber = $this->resolveCustomFieldNumber($contractId, $row, $label, $existing, $existingByLabel, $usedFieldNumbers, $projectId);
                if ($fieldNumber < 1) {
                    continue;
                }
                $usedFieldNumbers[] = $fieldNumber;
                $fieldNumbersByKey[$key] = $fieldNumber;
                unset($existingByLabel[$label]);
            }
        } catch (\Throwable $e) {
            Log::warning('[Smaregi] custom field label sync failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
        }

        return $fieldNumbersByKey;
    }

    /**
     * 保存済み番号 → 同名ラベル → 新規登録 の順で、スマレジ自由項目番号を決める。
     *
     * @param  array{key?: string, label?: string, smaregi_no?: int|null}  $row
     * @param  array<int, array{no: int, label: string}>  $existing
     * @param  array<string, int>  $existingByLabel
     * @param  list<int>  $usedFieldNumbers
     */
    private function resolveCustomFieldNumber(
        string $contractId,
        array $row,
        string $label,
        array $existing,
        array $existingByLabel,
        array $usedFieldNumbers,
        int $projectId,
    ): int {
        $savedFieldNumber = (int) ($row['smaregi_no'] ?? 0);
        if ($savedFieldNumber > 0 && isset($existing[$savedFieldNumber]) && ! in_array($savedFieldNumber, $usedFieldNumbers, true)) {
            $this->updateLabelIfChanged($contractId, $savedFieldNumber, $label, $existing[$savedFieldNumber]['label'], $projectId);

            return $savedFieldNumber;
        }
        if (isset($existingByLabel[$label]) && ! in_array($existingByLabel[$label], $usedFieldNumbers, true)) {
            return $existingByLabel[$label];
        }

        return $this->createLabel($contractId, $label, $projectId);
    }

    private function updateLabelIfChanged(string $contractId, int $no, string $label, string $current, int $projectId): void
    {
        if ($current === $label) {
            return;
        }
        $result = $this->smaregiApi->updateCustomerCustomFieldLabel($contractId, $no, ['label' => $label]);
        if ($result === null) {
            Log::warning('[Smaregi] custom field label update failed', [
                'project_id' => $projectId,
                'smaregi_no' => $no,
            ]);
        }
    }

    private function createLabel(string $contractId, string $label, int $projectId): int
    {
        $result = $this->smaregiApi->createCustomerCustomFieldLabel($contractId, ['label' => $label]);
        $no = is_array($result) ? (int) ($result['no'] ?? 0) : 0;
        if ($no < 1) {
            Log::warning('[Smaregi] custom field label create failed', [
                'project_id' => $projectId,
                'label' => $label,
            ]);
        }

        return $no;
    }

    /**
     * @return array<int, array{no: int, label: string}>|null
     */
    private function fetchExistingLabels(string $contractId): ?array
    {
        $result = $this->smaregiApi->getCustomerCustomFieldLabels($contractId, [
            'limit' => self::FETCH_LIMIT,
            'sort' => 'no',
        ]);
        if ($result === null) {
            return null;
        }
        $list = $this->extractList($result);
        $out = [];
        foreach ($list as $item) {
            if (! is_array($item)) {
                continue;
            }
            $no = (int) ($item['no'] ?? 0);
            $label = trim((string) ($item['label'] ?? ''));
            if ($no < 1 || $label === '') {
                continue;
            }
            $out[$no] = ['no' => $no, 'label' => $label];
        }

        return $out;
    }

    /**
     * @param  array<int, array{no: int, label: string}>  $existing
     * @return array<string, int>
     */
    private function indexByLabel(array $existing): array
    {
        $byLabel = [];
        foreach ($existing as $item) {
            $byLabel[$item['label']] = $item['no'];
        }

        return $byLabel;
    }

    /**
     * @param  array<int|string, string|int|float|bool|array|null>  $result
     * @return array<int|string, string|int|float|bool|array|null>
     */
    private function extractList(array $result): array
    {
        if ($result === [] || array_keys($result) === range(0, count($result) - 1)) {
            return $result;
        }
        $list = $result['custom_field_labels'] ?? $result['customFieldLabels'] ?? [];

        return is_array($list) ? $list : [];
    }
}
