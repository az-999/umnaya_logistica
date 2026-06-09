<?php

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Models\Notification;

class NotificationStatusService
{
    public function markSent(Notification $notification, string $providerRef): void
    {
        $notification->update([
            'status'        => NotificationStatus::Sent,
            'provider_ref'  => $providerRef,
            'sent_at'       => now(),
            'error_message' => null,
        ]);
    }

    public function markRejected(Notification $notification, string $errorMessage): void
    {
        $notification->update([
            'status'        => NotificationStatus::Rejected,
            'error_message' => $errorMessage,
        ]);
    }

    public function markDelivered(Notification $notification): void
    {
        $notification->update([
            'status'       => NotificationStatus::Delivered,
            'delivered_at' => now(),
        ]);
    }

    public function incrementAttempts(Notification $notification): void
    {
        $notification->increment('attempts');
    }
}
