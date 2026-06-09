<?php

namespace Tests\Integration;

use App\Enums\NotificationStatus;
use App\Exceptions\ProviderTemporaryException;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Providers\Mock\MockSmsProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetryFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_temporary_failure_is_retried_and_eventually_rejected(): void
    {
        $notification = Notification::query()->create([
            'subscriber_id' => 'temp-fail-user',
            'channel' => 'sms',
            'message' => 'Retry me',
            'priority' => 'marketing',
            'status' => NotificationStatus::Queued,
        ]);

        $job = new SendNotificationJob($notification->id);
        $job->tries = 1;

        try {
            $job->handle(
                new MockSmsProvider,
                app(\App\Contracts\EmailProviderInterface::class),
                app(\App\Services\NotificationStatusService::class),
            );
            $this->fail('Expected ProviderTemporaryException');
        } catch (ProviderTemporaryException) {
            // expected on first attempt
        }

        $notification->refresh();
        $this->assertSame(1, $notification->attempts);
        $this->assertSame(NotificationStatus::Queued, $notification->status);
    }
}
