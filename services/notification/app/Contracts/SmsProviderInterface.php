<?php

namespace App\Contracts;

use App\DTO\ProviderResult;

interface SmsProviderInterface
{
    public function send(string $subscriberId, string $message): ProviderResult;
}
