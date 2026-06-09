<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public const API_KEY = 'test-api-key';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeaders(['X-Api-Key' => self::API_KEY]);
    }

    protected function withoutApiKey(): static
    {
        $this->defaultHeaders = array_diff_key(
            $this->defaultHeaders,
            ['X-Api-Key' => true],
        );

        return $this;
    }

    public function createApplication(): Application
    {
        $this->setTestingEnvironment();

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        $app->make('config')->set([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'queue.default' => 'sync',
            'api.key' => self::API_KEY,
        ]);

        return $app;
    }

    private function setTestingEnvironment(): void
    {
        $vars = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'CACHE_STORE' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
            'API_KEY' => self::API_KEY,
        ];

        foreach ($vars as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
