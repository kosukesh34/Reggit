<?php

namespace App\Services\Smaregi;

final class SmaregiListResponse
{
    /**
     * @param  array<int|string, string|int|float|bool|array|null>  $result
     * @return array<int|string, string|int|float|bool|array|null>
     */
    public static function extract(array $result, string $listKey): array
    {
        if (array_is_list($result)) {
            return $result;
        }
        $list = $result[$listKey] ?? [];

        return is_array($list) ? $list : [];
    }
}
