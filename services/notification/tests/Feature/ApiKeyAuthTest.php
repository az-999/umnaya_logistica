<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_returns_401_without_api_key(): void
    {
        $response = $this->withoutApiKey()->postJson('/api/v1/notifications/bulk', [
            'channel' => 'sms',
            'message' => 'Hello',
            'recipient_ids' => ['sub-001'],
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Unauthorized.');
    }

    public function test_api_returns_401_with_invalid_api_key(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => 'wrong-key'])->postJson('/api/v1/notifications/bulk', [
            'channel' => 'sms',
            'message' => 'Hello',
            'recipient_ids' => ['sub-001'],
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Unauthorized.');
    }

    public function test_api_accepts_valid_api_key(): void
    {
        $response = $this->postJson('/api/v1/notifications/bulk', [
            'channel' => 'sms',
            'message' => 'Hello',
            'recipient_ids' => ['sub-001'],
            'priority' => 'marketing',
        ]);

        $response->assertStatus(202);
    }
}
