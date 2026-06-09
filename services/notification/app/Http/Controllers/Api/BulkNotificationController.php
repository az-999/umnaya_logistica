<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkNotificationRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class BulkNotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function store(BulkNotificationRequest $request): JsonResponse
    {
        $priority = $request->enum('priority', NotificationPriority::class)
            ?? NotificationPriority::Marketing;

        $result = $this->notificationService->sendBulk(
            channel: $request->enum('channel', NotificationChannel::class),
            message: $request->string('message')->toString(),
            recipientIds: $request->input('recipient_ids'),
            priority: $priority,
            idempotencyKey: $request->header('Idempotency-Key'),
        );

        $data = $result['notifications']
            ->map(fn ($n) => $this->notificationService->formatNotification($n))
            ->values();

        $status = $result['duplicate'] ? 200 : 202;

        return response()->json(['data' => $data], $status);
    }
}
