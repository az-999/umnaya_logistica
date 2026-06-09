<?php

namespace App\Providers;

use App\Contracts\EmailProviderInterface;
use App\Contracts\SmsProviderInterface;
use App\Providers\Mock\MockEmailProvider;
use App\Providers\Mock\MockSmsProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsProviderInterface::class, MockSmsProvider::class);
        $this->app->bind(EmailProviderInterface::class, MockEmailProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
