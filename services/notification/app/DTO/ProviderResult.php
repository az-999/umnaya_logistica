<?php

namespace App\DTO;

readonly class ProviderResult
{
    public function __construct(
        public bool $success,
        public ?string $providerRef = null,
        public ?string $errorMessage = null,
        public bool $temporaryFailure = false,
        public bool $scheduleDeliveryConfirmation = false,
    ) {
    }
}
