<?php

use Dinas\Shipping\Shipping;

describe('Configuration', function () {
    it('has default configuration values', function () {
        $config = config('dinas-shipping-sdk');

        expect($config)->toBeArray()
            ->and($config)->toHaveKeys(['token', 'base_url', 'timeout', 'debug']);
    });

    it('token can be set from config', function () {
        config(['dinas-shipping-sdk.token' => 'custom-token']);

        $shipping = app(Shipping::class);
        $configuration = $shipping->getConfiguration();

        expect($configuration->getAccessToken())->toBe('custom-token');
    });

    it('base_url can be set from config', function () {
        config(['dinas-shipping-sdk.base_url' => 'https://custom.example.com']);

        $shipping = app(Shipping::class);
        $configuration = $shipping->getConfiguration();

        expect($configuration->getHost())->toBe('https://custom.example.com');
    });

    it('debug can be enabled from config', function () {
        config(['dinas-shipping-sdk.debug' => true]);

        $shipping = app(Shipping::class);
        $configuration = $shipping->getConfiguration();

        expect($configuration->getDebug())->toBeTrue();
    });

    it('timeout is configurable', function () {
        config(['dinas-shipping-sdk.timeout' => 60]);

        $shipping = app(Shipping::class);

        expect($shipping)->toBeInstanceOf(Shipping::class);
    });
});
