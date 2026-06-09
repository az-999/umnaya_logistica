<?php

namespace App\Contracts;

use App\DTO\ProviderResult;

interface EmailProviderInterface
{
    public function send(string $subscriberId, string $message): ProviderResult;
}
