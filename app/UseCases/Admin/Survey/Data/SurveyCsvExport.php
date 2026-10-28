<?php

namespace App\UseCases\Admin\Survey\Data;

/** アンケート回答の CSV。1行目が見出し。 */
final class SurveyCsvExport
{
    /** @param list<list<string>> $rows */
    public function __construct(
        public readonly string $filename,
        public readonly array $rows,
    ) {}
}
