<?php

namespace App\UseCases\Member\Data;

/** 会員情報の表示用データ（連想配列）。不変。 */
class MemberData
{
    public function __construct(public array $data)
    {
    }

    /** @return array<string, string|int|float|bool|array|null> 会員情報の連想配列 */
    public function toArray(): array
    {
        return $this->data;
    }
}
