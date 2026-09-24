<?php

namespace App\UseCases\Admin\Settings\Data;

class ExistingMemberLinkPageView
{
    /**
     * @param  list<array{key: string, label: string, isChecked: bool}>  $fields
     */
    public function __construct(
        public bool $isEnabled,
        public string $description,
        public array $fields,
        public bool $canManageSettings,
        public bool $canLink,
    ) {}
}
