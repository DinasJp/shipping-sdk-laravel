<?php

use Dinas\Shipping\Shipping;

describe('Parameter Validation', function () {
    it('holdCars accepts valid items array', function () {
        $shipping = new Shipping();

        expect(fn () => $shipping->holdCars(['ABC123', 'DEF456']))
            ->not->toThrow(TypeError::class);
    });

    it('holdCars accepts ship_date_limit as array', function () {
        $shipping = new Shipping();

        expect(fn () => $shipping->holdCars(['ABC123'], [
            'date' => '2026-03-15',
            'after' => true,
        ]))->not->toThrow(TypeError::class);
    });

    it('withholdCars accepts reason as string', function () {
        $shipping = new Shipping();

        expect(fn () => $shipping->withholdCars(['ABC123'], 'Payment pending'))
            ->not->toThrow(TypeError::class);
    });

    it('withholdCars accepts null reason', function () {
        $shipping = new Shipping();

        expect(fn () => $shipping->withholdCars(['ABC123'], null))
            ->not->toThrow(TypeError::class);
    });

    it('setYardEta accepts array of items with chassis and eta', function () {
        $shipping = new Shipping();

        expect(fn () => $shipping->setYardEta([
            ['chassis' => 'ABC123', 'eta' => '2026-02-15'],
            ['chassis' => 'DEF456', 'eta' => '2026-02-16'],
        ]))->not->toThrow(TypeError::class);
    });

    it('getCars accepts array of parameters', function () {
        $shipping = new Shipping();

        expect(fn () => $shipping->getCars([
            'status' => 'pending',
            'chassis' => 'ABC123',
            'search' => 'Toyota',
            'port_code' => 'JPYOK',
            'voyage' => 'VES001',
            'vehicle_state' => 'used',
            'vehicle_type' => 'sedan',
            'photos' => true,
            'docs' => true,
            'on_yard' => true,
            'price_terms' => 'fob',
            'sort' => '-id',
            'per_page' => 50,
            'page' => 1,
        ]))->not->toThrow(TypeError::class);
    });

    it('storeCarPhotos accepts array of photo data', function () {
        $shipping = new Shipping();

        expect(fn () => $shipping->storeCarPhotos([
            ['chassis' => 'ABC123', 'album' => 'exterior', 'urls' => ['url1']],
        ]))->not->toThrow(TypeError::class);
    });

    it('storeCarDocuments accepts array of document data', function () {
        $shipping = new Shipping();

        expect(fn () => $shipping->storeCarDocuments([
            ['chassis' => 'ABC123', 'type' => 'invoice', 'url' => 'https://example.com/doc.pdf'],
        ]))->not->toThrow(TypeError::class);
    });
});

describe('Return Types', function () {
    it('getConfiguration returns Configuration instance', function () {
        $shipping = new Shipping();

        expect($shipping->getConfiguration())
            ->toBeInstanceOf(\Dinas\ShippingSdk\Configuration::class);
    });

    it('getHttpClient returns ClientInterface instance', function () {
        $shipping = new Shipping();

        expect($shipping->getHttpClient())
            ->toBeInstanceOf(\Psr\Http\Client\ClientInterface::class);
    });

    it('setHttpClient returns self', function () {
        $shipping = new Shipping();
        $client = Mockery::mock(\Psr\Http\Client\ClientInterface::class);

        $result = $shipping->setHttpClient($client);

        expect($result)->toBe($shipping);
    });

    it('cars returns CarsApi instance', function () {
        $shipping = new Shipping();

        expect($shipping->cars())
            ->toBeInstanceOf(\Dinas\ShippingSdk\Api\CarsApi::class);
    });

    it('carPhotos returns CarPhotosApi instance', function () {
        $shipping = new Shipping();

        expect($shipping->carPhotos())
            ->toBeInstanceOf(\Dinas\ShippingSdk\Api\CarPhotosApi::class);
    });

    it('carDocuments returns CarDocumentsApi instance', function () {
        $shipping = new Shipping();

        expect($shipping->carDocuments())
            ->toBeInstanceOf(\Dinas\ShippingSdk\Api\CarDocumentsApi::class);
    });

    it('voyages returns VoyagesApi instance', function () {
        $shipping = new Shipping();

        expect($shipping->voyages())
            ->toBeInstanceOf(\Dinas\ShippingSdk\Api\VoyagesApi::class);
    });

    it('webhooks returns WebhooksApi instance', function () {
        $shipping = new Shipping();

        expect($shipping->webhooks())
            ->toBeInstanceOf(\Dinas\ShippingSdk\Api\WebhooksApi::class);
    });
});
