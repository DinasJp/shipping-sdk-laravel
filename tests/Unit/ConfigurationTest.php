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

    it('takes timeout and debug as env() gives them, strings', function () {
        config(['dinas-shipping-sdk.timeout' => '60', 'dinas-shipping-sdk.debug' => '1']);
        app()->forgetInstance(Shipping::class);

        expect(app(Shipping::class)->getConfiguration()->getDebug())->toBeTrue();
    });

    it('keeps the token and host of each client apart', function () {
        $a = new Shipping('token-a', 'https://a.example.com');
        $b = new Shipping('token-b', 'https://b.example.com');

        expect($a->getConfiguration()->getAccessToken())->toBe('token-a')
            ->and($a->getConfiguration()->getHost())->toBe('https://a.example.com')
            ->and($b->getConfiguration()->getAccessToken())->toBe('token-b');
    });
});
