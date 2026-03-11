<?php

declare(strict_types=1);

namespace Andriichuk\AtomParkSmsChannel;

use Illuminate\Support\Facades\Notification;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AtomParkSmsChannelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-atompark-sms-channel')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->register(AtomParkServiceProvider::class);
    }

    public function packageBooted(): void
    {
        Notification::extend('atompark', static function ($app): AtomParkChannel {
            return $app->make(AtomParkChannel::class);
        });
    }
}
