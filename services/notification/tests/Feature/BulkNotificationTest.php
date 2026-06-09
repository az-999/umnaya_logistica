<?php

namespace Tests\Feature;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BulkNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_sms_returns_accepted(): void
    {
        $response = $this->postJson('/api/v1/notifications/bulk', [
            'channel' => 'sms',
            'message' => 'Hello',
            'recipient_ids' => ['sub-001', 'sub-002'],
            'priority' => 'marketing',
        ]);

        $response->assertStatus(202)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $item) {
            $this->assertContains($item['status'], [
                NotificationStatus::Queued->value,
                NotificationStatus::Sent->value,
                NotificationStatus::Delivered->value,
            ]);
        }

        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_validation_fails_for_invalid_channel(): void
    {
        $response = $this->postJson('/api/v1/notifications/bulk', [
            'channel' => 'push',
            'message' => 'Hello',
            'recipient_ids' => ['sub-001'],
        ]);

        $response->assertStatus(422);
    }

    public function test_idempotency_returns_same_notifications(): void
    {
        $payload = [
            'channel' => 'email',
            'message' => 'Promo',
            'recipient_ids' => ['sub-010'],
            'priority' => 'marketing',
        ];

        $first = $this->postJson('/api/v1/notifications/bulk', $payload, [
            'Idempotency-Key' => 'idem-123',
        ]);

        $second = $this->postJson('/api/v1/notifications/bulk', $payload, [
            'Idempotency-Key' => 'idem-123',
        ]);

        $first->assertStatus(202);
        $second->assertStatus(200)
            ->assertJsonPath('data.0.id', $first->json('data.0.id'));

        $this->assertDatabaseCount('notifications', 1);
    }
}
