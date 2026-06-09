<?php

namespace App\Jobs;

use App\Contracts\EmailProviderInterface;
use App\Contracts\SmsProviderInterface;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Exceptions\ProviderTemporaryException;
use App\Models\Notification;
use App\Services\NotificationStatusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public string $notificationId)
    {
    }

    public function handle(
        SmsProviderInterface $smsProvider,
        EmailProviderInterface $emailProvider,
        NotificationStatusService $statusService,
    ): void {
        $notification = Notification::query()->findOrFail($this->notificationId);

        if (in_array($notification->status, [NotificationStatus::Sent, NotificationStatus::Delivered, NotificationStatus::Rejected], true)) {
            return;
        }

        $statusService->incrementAttempts($notification);

        try {
            $result = match ($notification->channel) {
                NotificationChannel::Sms => $smsProvider->send($notification->subscriber_id, $notification->message),
                NotificationChannel::Email => $emailProvider->send($notification->subscriber_id, $notification->message),
            };
        } catch (ProviderTemporaryException $e) {
            throw $e;
        }

        if (! $result->success) {
            $statusService->markRejected($notification, $result->errorMessage ?? 'Delivery failed');

            return;
        }

        $statusService->markSent($notification, $result->providerRef ?? 'unknown');

        if ($result->scheduleDeliveryConfirmation) {
            ConfirmDeliveryJob::dispatch($notification->id)->delay(now()->addSeconds(2));
        }
    }
}
