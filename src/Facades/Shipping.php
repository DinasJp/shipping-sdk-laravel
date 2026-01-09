<?php

namespace Dinas\Shipping\Facades;

use Dinas\ShippingSdk\Api\CarsApi;
use Dinas\ShippingSdk\Api\VoyagesApi;
use Dinas\ShippingSdk\Api\WebhooksApi;
use Dinas\ShippingSdk\Configuration;
use Dinas\ShippingSdk\Model\Webhook;
use Illuminate\Support\Facades\Facade;
use Psr\Http\Client\ClientInterface;

/**
 * @method static Configuration getConfiguration()
 * @method static ClientInterface getHttpClient()
 * @method static \Dinas\Shipping\Shipping setHttpClient(ClientInterface $client)
 * @method static CarsApi cars()
 * @method static VoyagesApi voyages()
 * @method static WebhooksApi webhooks()
 * @method static mixed getCars(array $params = [])
 * @method static mixed getCarPhotos(array $params = [])
 * @method static mixed syncCars(array $cars)
 * @method static mixed storeCarPhotos(array $photos)
 * @method static mixed storeCarDocuments(array $documents)
 * @method static mixed getVoyages(array $params = [])
 * @method static mixed getVoyage(string $voyage)
 * @method static mixed getWebhooks()
 * @method static mixed getWebhook(int $id)
 * @method static mixed storeWebhook(Webhook|array $data)
 * @method static mixed updateWebhook(int $id, Webhook|array $data)
 * @method static mixed deleteWebhook(int $id)
 * @method static mixed toggleWebhook(int $id)
 * @method static mixed testWebhook(int $id)
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
