<?php

namespace Dinas\Shipping;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Dinas\Shipping\Commands\ShippingCommand;

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
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_shipping_sdk_laravel_table')
            ->hasCommand(ShippingCommand::class);
    }
}
