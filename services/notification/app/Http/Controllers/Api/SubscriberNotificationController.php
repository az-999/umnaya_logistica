<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriberNotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(string $subscriberId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::enum(NotificationStatus::class)],
            'channel' => ['sometimes', Rule::enum(NotificationChannel::class)],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Notification::query()
            ->where('subscriber_id', $subscriberId)
            ->orderByDesc('created_at');

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['channel'])) {
            $query->where('channel', $validated['channel']);
        }

        $paginator = $query->paginate($validated['per_page'] ?? 20);

        return response()->json([
            'data' => collect($paginator->items())
                ->map(fn ($n) => $this->notificationService->formatNotification($n))
                ->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
