<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Services\NotificationStatusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConfirmDeliveryJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $notificationId)
    {
    }

    public function handle(NotificationStatusService $statusService): void
    {
        $notification = Notification::query()->findOrFail($this->notificationId);

        if ($notification->status === NotificationStatus::Sent) {
            $statusService->markDelivered($notification);
        }
    }
}
