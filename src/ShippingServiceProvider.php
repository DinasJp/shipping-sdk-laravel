<?php

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
            ->hasCommand(ShippingWebhookSetup::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Shipping::class, function ($app) {
            return new Shipping(
                config('dinas-shipping-sdk.token'),
                config('dinas-shipping-sdk.base_url'),
                config('dinas-shipping-sdk.timeout'),
                config('dinas-shipping-sdk.debug', false),
            );
        });

        $this->app->alias(Shipping::class, 'dinas-shipping');
    }

    public function bootingPackage(): void
    {
        Route::macro('dinasShippingWebhooks', function ($url) {
            return Route::post($url, WebhooksController::class)->name('webhooks.dinas-shipping');
        });
    }
}
