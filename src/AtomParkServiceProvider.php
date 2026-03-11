<?php

declare(strict_types=1);

namespace Andriichuk\AtomParkSmsChannel;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

final class AtomParkServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(AtomParkClient::class, static function (): AtomParkClient {
            return new AtomParkClient(
                config('services.atompark.sms.sender'),
                config('services.atompark.sms.public_key'),
                config('services.atompark.sms.private_key'),
            );
        });
    }

    public function provides(): array
    {
        return [AtomParkClient::class];
    }
}
