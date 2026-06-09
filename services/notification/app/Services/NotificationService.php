<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

class NotificationService
{
    public const MARKETING_QUEUE = 'notifications.marketing';

    public function __construct(
        private readonly IdempotencyService $idempotencyService,
    ) {
    }

    /**
     * @param  array<int, string>  $recipientIds
     * @return array{duplicate: bool, notifications: Collection<int, Notification>}
     */
    public function sendBulk(
        NotificationChannel $channel,
        string $message,
        array $recipientIds,
        NotificationPriority $priority,
        ?string $idempotencyKey = null,
    ): array {
        if ($idempotencyKey !== null) {
            $cached = $this->idempotencyService->getStoredResponse($idempotencyKey);
            if ($cached !== null) {
                $ids = collect($cached['notification_ids']);
                $notifications = Notification::query()->whereIn('id', $ids)->get();

                return ['duplicate' => true, 'notifications' => $notifications];
            }

            if (! $this->idempotencyService->acquire($idempotencyKey)) {
                $cached = $this->idempotencyService->getStoredResponse($idempotencyKey);
                if ($cached !== null) {
                    $ids = collect($cached['notification_ids']);
                    $notifications = Notification::query()->whereIn('id', $ids)->get();

                    return ['duplicate' => true, 'notifications' => $notifications];
                }
            }
        }

        $notifications = collect();

        foreach ($recipientIds as $subscriberId) {
            $notification = $this->createNotification(
                $channel,
                $message,
                $subscriberId,
                $priority,
                $idempotencyKey,
            );

            if ($notification !== null) {
                $this->dispatchNotification($notification, $priority);
                $notifications->push($notification->fresh());
            }
        }

        if ($idempotencyKey !== null && $notifications->isNotEmpty()) {
            $this->idempotencyService->storeResponse($idempotencyKey, [
                'notification_ids' => $notifications->pluck('id')->all(),
            ]);
        }

        return ['duplicate' => false, 'notifications' => $notifications];
    }

    private function createNotification(
        NotificationChannel $channel,
        string $message,
        string $subscriberId,
        NotificationPriority $priority,
        ?string $idempotencyKey,
    ): ?Notification {
        try {
            return Notification::query()->create([
                'subscriber_id' => $subscriberId,
                'channel' => $channel,
                'message' => $message,
                'priority' => $priority,
                'status' => NotificationStatus::Queued,
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (QueryException $e) {
            if ($idempotencyKey === null) {
                throw $e;
            }

            return Notification::query()
                ->where('idempotency_key', $idempotencyKey)
                ->where('subscriber_id', $subscriberId)
                ->first();
        }
    }

    private function dispatchNotification(Notification $notification, NotificationPriority $priority): void
    {
        if ($priority === NotificationPriority::Transactional) {
            SendNotificationJob::dispatchSync($notification->id);

            return;
        }

        SendNotificationJob::dispatch($notification->id)
            ->onQueue(self::MARKETING_QUEUE);
    }

    public function handleDeliveryCallback(string $notificationId, NotificationStatus $status): ?Notification
    {
        $notification = Notification::query()->find($notificationId);

        if ($notification === null) {
            return null;
        }

        if ($status === NotificationStatus::Delivered) {
            $notification->update([
                'status' => NotificationStatus::Delivered,
                'delivered_at' => now(),
            ]);
        }

        if ($status === NotificationStatus::Rejected) {
            $notification->update([
                'status' => NotificationStatus::Rejected,
                'error_message' => $notification->error_message ?? 'Rejected by provider callback',
            ]);
        }

        return $notification->fresh();
    }

    public function formatNotification(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'subscriber_id' => $notification->subscriber_id,
            'channel' => $notification->channel->value,
            'message' => $notification->message,
            'status' => $notification->status->value,
            'priority' => $notification->priority->value,
            'provider_ref' => $notification->provider_ref,
            'error_message' => $notification->error_message,
            'attempts' => $notification->attempts,
            'created_at' => $notification->created_at?->toIso8601String(),
            'sent_at' => $notification->sent_at?->toIso8601String(),
            'delivered_at' => $notification->delivered_at?->toIso8601String(),
        ];
    }
}
