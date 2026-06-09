<?php

namespace App\Providers\Mock;

use App\Contracts\SmsProviderInterface;
use App\DTO\ProviderResult;
use App\Exceptions\ProviderTemporaryException;

class MockSmsProvider implements SmsProviderInterface
{
    public function send(string $subscriberId, string $message): ProviderResult
    {
        if (str_contains($subscriberId, 'temp-fail')) {
            throw new ProviderTemporaryException('SMS gateway temporarily unavailable');
        }

        if (str_contains($subscriberId, 'invalid')) {
            return new ProviderResult(
                success: false,
                errorMessage: 'Invalid phone number',
            );
        }

        return new ProviderResult(
            success: true,
            providerRef: 'sms-'.md5($subscriberId.$message),
            scheduleDeliveryConfirmation: true,
        );
    }
}
