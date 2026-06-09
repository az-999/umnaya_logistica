<?php

namespace Tests\Integration;

use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_job_updates_status_to_sent(): void
    {
        $notification = Notification::query()->create([
            'subscriber_id' => 'sub-flow',
            'channel' => 'sms',
            'message' => 'Integration test',
            'priority' => 'marketing',
            'status' => NotificationStatus::Queued,
        ]);

        SendNotificationJob::dispatchSync($notification->id);

        $notification->refresh();

        $this->assertContains($notification->status, [NotificationStatus::Sent, NotificationStatus::Delivered]);
        $this->assertNotNull($notification->provider_ref);
        $this->assertNotNull($notification->sent_at);
    }

    public function test_rejected_subscriber_marks_notification_rejected(): void
    {
        $notification = Notification::query()->create([
            'subscriber_id' => 'invalid-user',
            'channel' => 'email',
            'message' => 'Test',
            'priority' => 'marketing',
            'status' => NotificationStatus::Queued,
        ]);

        SendNotificationJob::dispatchSync($notification->id);

        $notification->refresh();

        $this->assertSame(NotificationStatus::Rejected, $notification->status);
        $this->assertNotNull($notification->error_message);
    }

    public function test_delivery_callback_updates_status(): void
    {
        $notification = Notification::query()->create([
            'subscriber_id' => 'sub-cb',
            'channel' => 'sms',
            'message' => 'Callback',
            'priority' => 'transactional',
            'status' => NotificationStatus::Sent,
            'sent_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/notifications/{$notification->id}/delivery-callback", [
            'status' => 'delivered',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', NotificationStatus::Delivered->value);

        $this->assertNotNull($notification->fresh()->delivered_at);
    }
}
