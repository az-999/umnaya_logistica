<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriberRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_returns_429_when_subscriber_hourly_limit_exceeded(): void
    {
        config(['rate_limits.subscriber_per_hour' => 2]);

        $payload = [
            'channel' => 'sms',
            'message' => 'Hello',
            'recipient_ids' => ['sub-rate-001'],
            'priority' => 'marketing',
        ];

        $this->postJson('/api/v1/notifications/bulk', $payload)->assertStatus(202);
        $this->postJson('/api/v1/notifications/bulk', $payload)->assertStatus(202);

        $response = $this->postJson('/api/v1/notifications/bulk', $payload);

        $response->assertStatus(429)
            ->assertJsonPath('message', 'Subscriber rate limit exceeded.')
            ->assertJsonPath('subscriber_ids', ['sub-rate-001']);
    }

    public function test_rate_limit_is_scoped_per_subscriber_and_channel(): void
    {
        config(['rate_limits.subscriber_per_hour' => 1]);

        $smsPayload = [
            'channel' => 'sms',
            'message' => 'SMS',
            'recipient_ids' => ['sub-a'],
            'priority' => 'marketing',
        ];

        $emailPayload = [
            'channel' => 'email',
            'message' => 'Email',
            'recipient_ids' => ['sub-a'],
            'priority' => 'marketing',
        ];

        $this->postJson('/api/v1/notifications/bulk', $smsPayload)->assertStatus(202);
        $this->postJson('/api/v1/notifications/bulk', $emailPayload)->assertStatus(202);
    }

    public function test_idempotent_retry_does_not_consume_rate_limit(): void
    {
        config(['rate_limits.subscriber_per_hour' => 1]);

        $payload = [
            'channel' => 'sms',
            'message' => 'Hello',
            'recipient_ids' => ['sub-idem-rate'],
            'priority' => 'marketing',
        ];

        $headers = ['Idempotency-Key' => 'idem-rate-1'];

        $this->postJson('/api/v1/notifications/bulk', $payload, $headers)->assertStatus(202);
        $this->postJson('/api/v1/notifications/bulk', $payload, $headers)->assertStatus(200);
    }
}
