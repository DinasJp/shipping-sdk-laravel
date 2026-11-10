<?php

declare(strict_types=1);

namespace Dinas\Shipping;

use Dinas\Shipping\Commands\ShippingWebhookSetup;
use Dinas\Shipping\Http\Controllers\WebhooksController;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ShippingServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('shipping-sdk-laravel')
            ->hasConfigFile([
                'dinas-shipping-sdk',
            ])
            ->hasCommands([
                ShippingWebhookSetup::class,
            ])
            ->hasMigrations([
                'create_webhook_jobs_table',
            ]);
    }

    public function packageRegistered(): void
    {
        // the constructor reads the config itself, where env() strings like a timeout of "60" pass its int/bool types
        $this->app->singleton(Shipping::class, fn () => new Shipping);

        $this->app->alias(Shipping::class, 'dinas-shipping');
    }

    public function bootingPackage(): void
    {
        Route::macro('dinasShippingWebhooks', function ($url) {
            return Route::post($url, WebhooksController::class)->name('webhooks.dinas-shipping');
        });
    }
}
