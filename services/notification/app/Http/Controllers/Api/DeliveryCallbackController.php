<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryCallbackRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class DeliveryCallbackController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function store(string $id, DeliveryCallbackRequest $request): JsonResponse
    {
        $status = $request->enum('status', NotificationStatus::class);

        $notification = $this->notificationService->handleDeliveryCallback($id, $status);

        if ($notification === null) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        return response()->json([
            'data' => $this->notificationService->formatNotification($notification),
        ]);
    }
}
