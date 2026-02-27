<?php

declare(strict_types=1);

namespace Dinas\Shipping\Facades;

use Dinas\Shipping\DTOs\StoreResult;
use Dinas\ShippingSdk\Api\CarDocumentsApi;
use Dinas\ShippingSdk\Api\CarPhotosApi;
use Dinas\ShippingSdk\Api\CarsApi;
use Dinas\ShippingSdk\Api\VoyagesApi;
use Dinas\ShippingSdk\Api\WebhooksApi;
use Dinas\ShippingSdk\Configuration;
use Dinas\ShippingSdk\Model\ActionResponse;
use Dinas\ShippingSdk\Model\Webhook;
use Illuminate\Support\Facades\Facade;
use Psr\Http\Client\ClientInterface;

/**
 * @method static Configuration getConfiguration()
 * @method static ClientInterface getHttpClient()
 * @method static \Dinas\Shipping\Shipping setHttpClient(ClientInterface $client)
 * @method static CarsApi cars()
 * @method static CarPhotosApi carPhotos()
 * @method static CarDocumentsApi carDocuments()
 * @method static VoyagesApi voyages()
 * @method static WebhooksApi webhooks()
 * @method static mixed getCars(array $params = [])
 * @method static mixed getCarPhotos(array $params = [])
 * @method static mixed syncCars(array $cars)
 * @method static mixed holdCars(array $items, ?array $shipDateLimit = null)
 * @method static mixed releaseCars(array $items)
 * @method static mixed withholdCars(array $items, ?string $reason = null)
 * @method static mixed grantCars(array $items)
 * @method static mixed setYardEta(array $items)
 * @method static StoreResult storeCarPhotos(array $photos, ?callable $onResolve = null)
 * @method static StoreResult storeCarPhotoFiles(array $photos, ?callable $onResolve = null)
 * @method static StoreResult storeCarDocuments(array $documents, ?callable $onResolve = null)
 * @method static StoreResult storeCarDocumentFiles(array $documents, ?callable $onResolve = null)
 * @method static mixed getVoyages(array $params = [])
 * @method static mixed getVoyage(int $voyage)
 * @method static array getWebhooks()
 * @method static Webhook getWebhook(string $name)
 * @method static Webhook storeWebhook(Webhook|array $data)
 * @method static Webhook updateWebhook(string $name, Webhook|array $data)
 * @method static ActionResponse deleteWebhook(string $name)
 * @method static ActionResponse toggleWebhook(string $name)
 * @method static ActionResponse testWebhook(string $name)
 *
 * @see \Dinas\Shipping\Shipping
 */
class Shipping extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Dinas\Shipping\Shipping::class;
    }
}
