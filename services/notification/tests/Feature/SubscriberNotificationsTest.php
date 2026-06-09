<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriberNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_subscriber_notifications_with_filters(): void
    {
        Notification::query()->create([
            'subscriber_id' => 'sub-100',
            'channel' => NotificationChannel::Sms,
            'message' => 'A',
            'priority' => NotificationPriority::Marketing,
            'status' => NotificationStatus::Sent,
        ]);

        Notification::query()->create([
            'subscriber_id' => 'sub-100',
            'channel' => NotificationChannel::Email,
            'message' => 'B',
            'priority' => NotificationPriority::Marketing,
            'status' => NotificationStatus::Queued,
        ]);

        Notification::query()->create([
            'subscriber_id' => 'sub-200',
            'channel' => NotificationChannel::Sms,
            'message' => 'C',
            'priority' => NotificationPriority::Marketing,
            'status' => NotificationStatus::Sent,
        ]);

        $response = $this->getJson('/api/v1/subscribers/sub-100/notifications?status=sent&channel=sms');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'A');
    }
}
