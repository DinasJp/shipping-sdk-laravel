<?php

use Dinas\Shipping\Facades\Shipping as ShippingFacade;
use Dinas\Shipping\Shipping;
use Dinas\ShippingSdk\Api\CarDocumentsApi;
use Dinas\ShippingSdk\Api\CarPhotosApi;
use Dinas\ShippingSdk\Api\CarsApi;
use Dinas\ShippingSdk\Api\VoyagesApi;
use Dinas\ShippingSdk\Api\WebhooksApi;

describe('Service Provider', function () {
    it('registers shipping singleton in container', function () {
        $shipping1 = app(Shipping::class);
        $shipping2 = app(Shipping::class);

        expect($shipping1)->toBeInstanceOf(Shipping::class)
            ->and($shipping1)->toBe($shipping2);
    });

    it('facade resolves to shipping instance', function () {
        $instance = ShippingFacade::getFacadeRoot();

        expect($instance)->toBeInstanceOf(Shipping::class);
    });

    it('config is published', function () {
        expect(config('dinas-shipping-sdk'))->toBeArray()
            ->and(config('dinas-shipping-sdk.token'))->not->toBeNull();
    });
});

describe('Facade Methods', function () {
    it('can call getCars through facade', function () {
        expect(fn () => ShippingFacade::cars())->not->toThrow(Exception::class);
    });

    it('can access all API instances through facade', function () {
        expect(ShippingFacade::cars())->toBeInstanceOf(CarsApi::class)
            ->and(ShippingFacade::carPhotos())->toBeInstanceOf(CarPhotosApi::class)
            ->and(ShippingFacade::carDocuments())->toBeInstanceOf(CarDocumentsApi::class)
            ->and(ShippingFacade::voyages())->toBeInstanceOf(VoyagesApi::class)
            ->and(ShippingFacade::webhooks())->toBeInstanceOf(WebhooksApi::class);
    });
});
