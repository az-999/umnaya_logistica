<?php

namespace Tests\Feature;

use App\Enums\NotificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TransactionalPriorityTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactional_is_processed_without_queue(): void
    {
        $response = $this->postJson('/api/v1/notifications/bulk', [
            'channel' => 'sms',
            'message' => 'Code 1234',
            'recipient_ids' => ['sub-tx'],
            'priority' => 'transactional',
        ]);

        $response->assertStatus(202);

        $this->assertContains($response->json('data.0.status'), [
            NotificationStatus::Sent->value,
            NotificationStatus::Delivered->value,
        ]);
    }

    public function test_marketing_pushes_to_queue_when_not_sync(): void
    {
        config(['queue.default' => 'rabbitmq']);

        Queue::fake();

        $this->postJson('/api/v1/notifications/bulk', [
            'channel' => 'sms',
            'message' => 'Sale!',
            'recipient_ids' => ['sub-mk'],
            'priority' => 'marketing',
        ]);

        Queue::assertPushed(\App\Jobs\SendNotificationJob::class);
    }
}
