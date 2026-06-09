<?php

namespace App\Exceptions;

use Exception;

class SubscriberRateLimitExceededException extends Exception
{
    /**
     * @param  array<int, string>  $subscriberIds
     */
    public function __construct(
        public readonly array $subscriberIds,
        public readonly int $retryAfterSeconds,
    ) {
        parent::__construct('Subscriber rate limit exceeded.');
    }
}
