<?php

namespace App\UseCases\Admin\Announcement\Data;

class StoreDeliveryOutput
{
    public bool $isScheduled;

    public bool $hasValidationError;

    public string $message;

    public ?string $validationField;

    public function __construct(
        bool $isScheduled,
        bool $hasValidationError,
        string $message,
        ?string $validationField = null
    ) {
        $this->isScheduled = $isScheduled;
        $this->hasValidationError = $hasValidationError;
        $this->message = $message;
        $this->validationField = $validationField;
    }
}
