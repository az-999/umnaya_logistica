<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Exceptions\SubscriberRateLimitExceededException;
use Illuminate\Support\Facades\RateLimiter;

class SubscriberRateLimitService
{
    private const DECAY_SECONDS = 3600;

    /**
     * @param  array<int, string>  $recipientIds
     */
    public function assertRecipientsAllowed(array $recipientIds, NotificationChannel $channel): void
    {
        $counts = array_count_values($recipientIds);
        $blocked = [];
        $retryAfterSeconds = 0;

        foreach ($counts as $subscriberId => $plannedSends) {
            $key = $this->key($subscriberId, $channel);
            $limit = config('rate_limits.subscriber_per_hour');
            $remaining = $limit - RateLimiter::attempts($key);

            if ($plannedSends > $remaining) {
                $blocked[] = $subscriberId;
                $retryAfterSeconds = max($retryAfterSeconds, RateLimiter::availableIn($key));
            }
        }

        if ($blocked !== []) {
            throw new SubscriberRateLimitExceededException($blocked, $retryAfterSeconds);
        }
    }

    public function recordSend(string $subscriberId, NotificationChannel $channel): void
    {
        RateLimiter::hit($this->key($subscriberId, $channel), self::DECAY_SECONDS);
    }

    private function key(string $subscriberId, NotificationChannel $channel): string
    {
        return 'subscriber:'.$subscriberId.':'.$channel->value;
    }
}
