<?php

use Dinas\Shipping\Shipping;
use Dinas\ShippingSdk\Api\CarDocumentsApi;
use Dinas\ShippingSdk\Api\CarPhotosApi;
use Dinas\ShippingSdk\Api\CarsApi;
use Dinas\ShippingSdk\Api\VoyagesApi;
use Dinas\ShippingSdk\Api\WebhooksApi;
use Dinas\ShippingSdk\Configuration;
use Psr\Http\Client\ClientInterface;

beforeEach(function () {
    config([
        'dinas-shipping-sdk.token' => 'test-token',
        'dinas-shipping-sdk.base_url' => 'https://test.example.com',
        'dinas-shipping-sdk.timeout' => 30,
        'dinas-shipping-sdk.debug' => false,
    ]);
});

describe('Shipping Class Initialization', function () {
    it('can be instantiated', function () {
        $shipping = new Shipping;

        expect($shipping)->toBeInstanceOf(Shipping::class);
    });

    it('initializes with config values', function () {
        $shipping = new Shipping;
        $config = $shipping->getConfiguration();

        expect($config)->toBeInstanceOf(Configuration::class)
            ->and($config->getAccessToken())->toBe('test-token')
            ->and($config->getHost())->toBe('https://test.example.com');
    });

    it('can override config values in constructor', function () {
        $shipping = new Shipping(
            accessToken: 'custom-token',
            baseUrl: 'https://custom.example.com',
            timeout: 60,
            debug: true
        );

        $config = $shipping->getConfiguration();

        expect($config->getAccessToken())->toBe('custom-token')
            ->and($config->getHost())->toBe('https://custom.example.com')
            ->and($config->getDebug())->toBeTrue();
    });

    it('returns http client instance', function () {
        $shipping = new Shipping;

        expect($shipping->getHttpClient())->toBeInstanceOf(ClientInterface::class);
    });

    it('can set custom http client', function () {
        $shipping = new Shipping;
        $customClient = Mockery::mock(ClientInterface::class);

        $result = $shipping->setHttpClient($customClient);

        expect($result)->toBe($shipping)
            ->and($shipping->getHttpClient())->toBe($customClient);
    });
});

describe('API Instances', function () {
    it('returns CarsApi instance', function () {
        $shipping = new Shipping;

        expect($shipping->cars())->toBeInstanceOf(CarsApi::class);
    });

    it('returns same CarsApi instance on multiple calls', function () {
        $shipping = new Shipping;
        $api1 = $shipping->cars();
        $api2 = $shipping->cars();

        expect($api1)->toBe($api2);
    });

    it('returns CarPhotosApi instance', function () {
        $shipping = new Shipping;

        expect($shipping->carPhotos())->toBeInstanceOf(CarPhotosApi::class);
    });

    it('returns CarDocumentsApi instance', function () {
        $shipping = new Shipping;

        expect($shipping->carDocuments())->toBeInstanceOf(CarDocumentsApi::class);
    });

    it('returns VoyagesApi instance', function () {
        $shipping = new Shipping;

        expect($shipping->voyages())->toBeInstanceOf(VoyagesApi::class);
    });

    it('returns WebhooksApi instance', function () {
        $shipping = new Shipping;

        expect($shipping->webhooks())->toBeInstanceOf(WebhooksApi::class);
    });

    it('resets API instances when http client is changed', function () {
        $shipping = new Shipping;
        $api1 = $shipping->cars();

        $customClient = Mockery::mock(ClientInterface::class);
        $shipping->setHttpClient($customClient);

        $api2 = $shipping->cars();

        expect($api1)->not->toBe($api2);
    });
});

describe('Cars API Methods', function () {
    it('calls getCars with correct parameters', function () {
        $shipping = new Shipping;
        $carsApi = Mockery::mock(CarsApi::class);

        $carsApi->shouldReceive('getCars')
            ->once()
            ->with(
                'pending',
                'ABC123',
                'Toyota',
                'JPYOK',
                'VES001',
                'used',
                'sedan',
                true,
                true,
                true,
                'fob',
                '-id',
                50,
                1
            )
            ->andReturn(['data' => []]);

        $reflection = new ReflectionClass($shipping);
        $property = $reflection->getProperty('carsApi');
        $property->setAccessible(true);
        $property->setValue($shipping, $carsApi);

        $shipping->getCars([
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
        ]);

        expect(true)->toBeTrue();
    });

    it('calls syncCars with car data', function () {
        $shipping = new Shipping;
        $carsApi = Mockery::mock(CarsApi::class);

        $carData = [
            ['chassis' => 'ABC123', 'make' => 'Toyota'],
            ['chassis' => 'DEF456', 'make' => 'Honda'],
        ];

        $carsApi->shouldReceive('syncCars')
            ->once()
            ->with($carData)
            ->andReturn(['success' => true]);

        $reflection = new ReflectionClass($shipping);
        $property = $reflection->getProperty('carsApi');
        $property->setAccessible(true);
        $property->setValue($shipping, $carsApi);

        $result = $shipping->syncCars($carData);

        expect($result)->toBe(['success' => true]);
    });

    it('calls holdCars with items and ship date limit', function () {
        $shipping = new Shipping;
        $carsApi = Mockery::mock(CarsApi::class);

        $carsApi->shouldReceive('holdCars')
            ->once()
            ->andReturn(['success' => true]);

        $reflection = new ReflectionClass($shipping);
        $property = $reflection->getProperty('carsApi');
        $property->setAccessible(true);
        $property->setValue($shipping, $carsApi);

        $shipping->holdCars(['ABC123', 'DEF456'], [
            'date' => '2026-03-15',
            'after' => true,
        ]);

        expect(true)->toBeTrue();
    });

    it('calls holdCars without ship date limit', function () {
        $shipping = new Shipping;
        $carsApi = Mockery::mock(CarsApi::class);

        $carsApi->shouldReceive('holdCars')
            ->once()
            ->andReturn(['success' => true]);

        $reflection = new ReflectionClass($shipping);
        $property = $reflection->getProperty('carsApi');
        $property->setAccessible(true);
        $property->setValue($shipping, $carsApi);

        $shipping->holdCars(['ABC123']);

        expect(true)->toBeTrue();
    });

    it('calls releaseCars with items', function () {
        $shipping = new Shipping;
        $carsApi = Mockery::mock(CarsApi::class);

        $carsApi->shouldReceive('releaseCars')
            ->once()
            ->andReturn(['success' => true]);

        $reflection = new ReflectionClass($shipping);
        $property = $reflection->getProperty('carsApi');
        $property->setAccessible(true);
        $property->setValue($shipping, $carsApi);

        $shipping->releaseCars(['ABC123', 'DEF456']);

        expect(true)->toBeTrue();
    });

    it('calls withholdCars with reason', function () {
        $shipping = new Shipping;
        $carsApi = Mockery::mock(CarsApi::class);

        $carsApi->shouldReceive('withholdCars')
            ->once()
            ->andReturn(['success' => true]);

        $reflection = new ReflectionClass($shipping);
        $property = $reflection->getProperty('carsApi');
        $property->setAccessible(true);
        $property->setValue($shipping, $carsApi);

        $shipping->withholdCars(['ABC123'], 'Payment pending');

        expect(true)->toBeTrue();
    });

    it('calls grantCars with items', function () {
        $shipping = new Shipping;
        $carsApi = Mockery::mock(CarsApi::class);

        $carsApi->shouldReceive('grantCars')
            ->once()
            ->andReturn(['success' => true]);

        $reflection = new ReflectionClass($shipping);
        $property = $reflection->getProperty('carsApi');
        $property->setAccessible(true);
        $property->setValue($shipping, $carsApi);

        $shipping->grantCars(['ABC123']);

        expect(true)->toBeTrue();
    });

    it('calls setYardEta with items', function () {
        $shipping = new Shipping;
        $carsApi = Mockery::mock(CarsApi::class);

        $carsApi->shouldReceive('setYardEta')
            ->once()
            ->andReturn(['success' => true]);

        $reflection = new ReflectionClass($shipping);
        $property = $reflection->getProperty('carsApi');
        $property->setAccessible(true);
        $property->setValue($shipping, $carsApi);

        $shipping->setYardEta([
            ['chassis' => 'ABC123', 'eta' => '2026-02-15'],
            ['chassis' => 'DEF456', 'eta' => '2026-02-16'],
        ]);

        expect(true)->toBeTrue();
    });
});

describe('Photos API Methods', function () {
    it('calls getCarPhotos with parameters', function () {
        $shipping = new Shipping;
        $photosApi = Mockery::mock(CarPhotosApi::class);

        $photosApi->shouldReceive('getCarPhotos')
            ->once()
            ->andReturn(['data' => []]);

        $reflection = new ReflectionClass($shipping);
        $property = $reflection->getProperty('carPhotosApi');
        $property->setAccessible(true);
        $property->setValue($shipping, $photosApi);

        $shipping->getCarPhotos([
            'chassis' => 'ABC123',
            'voyage' => 'VES001',
        ]);

        expect(true)->toBeTrue();
    });

    it('calls storeCarPhotos with photo data', function () {
        $shipping = new Shipping;
        $photosApi = Mockery::mock(CarPhotosApi::class);

        $photoData = [
            ['chassis' => 'ABC123', 'album' => 'exterior', 'urls' => ['url1', 'url2']],
        ];

        $photosApi->shouldReceive('storeCarPhotoUrls')
            ->once()
            ->with($photoData)
            ->andReturn(['success' => true]);

        $reflection = new ReflectionClass($shipping);
        $property = $reflection->getProperty('carPhotosApi');
        $property->setAccessible(true);
        $property->setValue($shipping, $photosApi);

        $result = $shipping->storeCarPhotos($photoData);

        expect($result)->toBe(['success' => true]);
    });

    it('calls storeCarPhotoFiles with file data', function () {
        $shipping = new Shipping;
        $photosApi = Mockery::mock(CarPhotosApi::class);

        $fileData = [
            ['chassis' => 'ABC123', 'album' => 'exterior', 'files' => []],
        ];

        $photosApi->shouldReceive('storeCarPhotoFiles')
            ->once()
            ->with($fileData)
            ->andReturn(['success' => true]);

        $reflection = new ReflectionClass($shipping);
        $property = $reflection->getProperty('carPhotosApi');
        $property->setAccessible(true);
        $property->setValue($shipping, $photosApi);

        $result = $shipping->storeCarPhotoFiles($fileData);

        expect($result)->toBe(['success' => true]);
    });
});

describe('Documents API Methods', function () {
    it('calls storeCarDocuments with document data', function () {
        $shipping = new Shipping;
        $docsApi = Mockery::mock(CarDocumentsApi::class);

        $docData = [
            ['chassis' => 'ABC123', 'type' => 'invoice', 'url' => 'https://example.com/doc.pdf'],
        ];

        $docsApi->shouldReceive('storeCarDocumentUrls')
            ->once()
            ->with($docData)
            ->andReturn(['success' => true]);

        $reflection = new ReflectionClass($shipping);
        $property = $reflection->getProperty('carDocumentsApi');
        $property->setAccessible(true);
        $property->setValue($shipping, $docsApi);

        $result = $shipping->storeCarDocuments($docData);

        expect($result)->toBe(['success' => true]);
    });

    it('calls storeCarDocumentFiles with file data', function () {
        $shipping = new Shipping;
        $docsApi = Mockery::mock(CarDocumentsApi::class);

        $fileData = [
            ['chassis' => 'ABC123', 'type' => 'invoice', 'file' => null],
        ];

        $docsApi->shouldReceive('storeCarDocumentFiles')
            ->once()
            ->with($fileData)
            ->andReturn(['success' => true]);

        $reflection = new ReflectionClass($shipping);
        $property = $reflection->getProperty('carDocumentsApi');
        $property->setAccessible(true);
        $property->setValue($shipping, $docsApi);

        $result = $shipping->storeCarDocumentFiles($fileData);

        expect($result)->toBe(['success' => true]);
    });
});

describe('Voyages API Methods', function () {
    it('calls getVoyages with parameters', function () {
        $shipping = new Shipping;
        $voyagesApi = Mockery::mock(VoyagesApi::class);

        $voyagesApi->shouldReceive('getVoyages')
            ->once()
            ->with('-id', 25, 1)
            ->andReturn(['data' => []]);

        $reflection = new ReflectionClass($shipping);
        $property = $reflection->getProperty('voyagesApi');
        $property->setAccessible(true);
        $property->setValue($shipping, $voyagesApi);

        $shipping->getVoyages([
            'sort' => '-id',
            'per_page' => 25,
            'page' => 1,
        ]);

        expect(true)->toBeTrue();
    });

    it('calls getVoyage with voyage identifier', function () {
        $shipping = new Shipping;
        $voyagesApi = Mockery::mock(VoyagesApi::class);

        $voyagesApi->shouldReceive('getVoyage')
            ->once()
            ->with('VES001')
            ->andReturn(['id' => 'VES001']);

        $reflection = new ReflectionClass($shipping);
        $property = $reflection->getProperty('voyagesApi');
        $property->setAccessible(true);
        $property->setValue($shipping, $voyagesApi);

        $result = $shipping->getVoyage('VES001');

        expect($result)->toBe(['id' => 'VES001']);
    });
});
