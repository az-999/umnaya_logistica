<?php

namespace App\Providers\Mock;

use App\Contracts\EmailProviderInterface;
use App\DTO\ProviderResult;
use App\Exceptions\ProviderTemporaryException;

class MockEmailProvider implements EmailProviderInterface
{
    public function send(string $subscriberId, string $message): ProviderResult
    {
        if (str_contains($subscriberId, 'temp-fail')) {
            throw new ProviderTemporaryException('Email gateway temporarily unavailable');
        }

        if (str_contains($subscriberId, 'invalid')) {
            return new ProviderResult(
                success: false,
                errorMessage: 'Invalid email address',
            );
        }

        return new ProviderResult(
            success: true,
            providerRef: 'email-'.md5($subscriberId.$message),
            scheduleDeliveryConfirmation: true,
        );
    }
}
