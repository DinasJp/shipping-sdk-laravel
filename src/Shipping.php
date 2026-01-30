<?php

declare(strict_types=1);

namespace Dinas\Shipping;

use Dinas\ShippingSdk\Api\CarDocumentsApi;
use Dinas\ShippingSdk\Api\CarPhotosApi;
use Dinas\ShippingSdk\Api\CarsApi;
use Dinas\ShippingSdk\Api\VoyagesApi;
use Dinas\ShippingSdk\Api\WebhooksApi;
use Dinas\ShippingSdk\Configuration;
use Dinas\ShippingSdk\Model\GrantCarsRequest;
use Dinas\ShippingSdk\Model\HoldCarsRequest;
use Dinas\ShippingSdk\Model\ReleaseCarsRequest;
use Dinas\ShippingSdk\Model\SetYardEtaRequest;
use Dinas\ShippingSdk\Model\Webhook;
use Dinas\ShippingSdk\Model\WithholdCarsRequest;
use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;

class Shipping
{
    protected Configuration $configuration;

    protected ClientInterface $httpClient;

    protected ?CarsApi $carsApi = null;

    protected ?CarPhotosApi $carPhotosApi = null;

    protected ?CarDocumentsApi $carDocumentsApi = null;

    protected ?VoyagesApi $voyagesApi = null;

    protected ?WebhooksApi $webhooksApi = null;

    public function __construct(?string $accessToken = null, ?string $baseUrl = null, ?int $timeout = null, bool $debug = false)
    {
        $accessToken = $accessToken ?? config('dinas-shipping-sdk.token');
        $baseUrl = $baseUrl ?? config('dinas-shipping-sdk.base_url');
        $timeout = $timeout ?? config('dinas-shipping-sdk.timeout');
        $debug = $debug || config('dinas-shipping-sdk.debug');

        $this->configuration = Configuration::getDefaultConfiguration()
            ->setAccessToken($accessToken)
            ->setHost($baseUrl)
            ->setDebug($debug);

        $this->httpClient = new Client([
            'timeout' => $timeout,
        ]);
    }

    /**
     * Get the SDK configuration instance.
     */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * Get the HTTP client instance.
     */
    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    /**
     * Set a custom HTTP client.
     */
    public function setHttpClient(ClientInterface $client): static
    {
        $this->httpClient = $client;

        // Reset API instances to use new client
        $this->carsApi = null;
        $this->carPhotosApi = null;
        $this->carDocumentsApi = null;
        $this->voyagesApi = null;
        $this->webhooksApi = null;

        return $this;
    }

    /**
     * Get the Cars API instance.
     */
    public function cars(): CarsApi
    {
        if ($this->carsApi === null) {
            $this->carsApi = new CarsApi($this->httpClient, $this->configuration);
        }

        return $this->carsApi;
    }

    /**
     * Get the Car Photos API instance.
     */
    public function carPhotos(): CarPhotosApi
    {
        if ($this->carPhotosApi === null) {
            $this->carPhotosApi = new CarPhotosApi($this->httpClient, $this->configuration);
        }

        return $this->carPhotosApi;
    }

    /**
     * Get the Car Documents API instance.
     */
    public function carDocuments(): CarDocumentsApi
    {
        if ($this->carDocumentsApi === null) {
            $this->carDocumentsApi = new CarDocumentsApi($this->httpClient, $this->configuration);
        }

        return $this->carDocumentsApi;
    }

    /**
     * Get the Voyages API instance.
     */
    public function voyages(): VoyagesApi
    {
        if ($this->voyagesApi === null) {
            $this->voyagesApi = new VoyagesApi($this->httpClient, $this->configuration);
        }

        return $this->voyagesApi;
    }

    /**
     * Get the Webhooks API instance.
     */
    public function webhooks(): WebhooksApi
    {
        if ($this->webhooksApi === null) {
            $this->webhooksApi = new WebhooksApi($this->httpClient, $this->configuration);
        }

        return $this->webhooksApi;
    }

    /*
    |--------------------------------------------------------------------------
    | Cars API Shortcuts
    |--------------------------------------------------------------------------
    */

    /**
     * Get paginated list of cars.
     *
     * @param  array<string, mixed>  $params  Optional query parameters:
     *                                        - status: Filter by car status
     *                                        - chassis: Filter by chassis number (multiple values separated by spaces)
     *                                        - search: Search by partial chassis, make, model
     *                                        - port_code: Filter by port code
     *                                        - voyage: Filter by voyage
     *                                        - vehicle_state: Filter by vehicle state
     *                                        - vehicle_type: Filter by vehicle type
     *                                        - photos: Filter by photos presence
     *                                        - docs: Filter by documents presence
     *                                        - on_yard: Filter by yard presence
     *                                        - price_terms: Filter by price terms
     *                                        - sort: Sort field. Prefix with - for descending. default: -id
     *                                        - per_page: Number of items per page. default: 100
     *                                        - page: Page number. default: 1
     */
    public function getCars(array $params = []): mixed
    {
        return $this->cars()->getCars(
            $params['status'] ?? null,
            $params['chassis'] ?? null,
            $params['search'] ?? null,
            $params['port_code'] ?? null,
            $params['voyage'] ?? null,
            $params['vehicle_state'] ?? null,
            $params['vehicle_type'] ?? null,
            $params['photos'] ?? null,
            $params['docs'] ?? null,
            $params['on_yard'] ?? null,
            $params['price_terms'] ?? null,
            $params['sort'] ?? null,
            $params['per_page'] ?? null,
            $params['page'] ?? null,
        );
    }

    /**
     * Get car photos.
     *
     * @param  array<string, mixed>  $params  Optional query parameters
     */
    public function getCarPhotos(array $params = []): mixed
    {
        return $this->carPhotos()->getCarPhotos(
            $params['status'] ?? null,
            $params['chassis'] ?? null,
            $params['search'] ?? null,
            $params['voyage'] ?? null,
            $params['photos'] ?? null,
            $params['on_yard'] ?? null,
            $params['sort'] ?? null,
            $params['per_page'] ?? null,
            $params['page'] ?? null,
        );
    }

    /**
     * Create or update cars (sync).
     *
     * @param  array<int, mixed>  $cars  Array of car data to sync
     */
    public function syncCars(array $cars): mixed
    {
        return $this->cars()->syncCars($cars);
    }

    /**
     * Hold cars from shipping.
     *
     * @param  array<int, string>  $items  Array of chassis numbers
     * @param  array<string, mixed>|null  $shipDateLimit  Optional ship date limit with 'date' and 'after' keys
     */
    public function holdCars(array $items, ?array $shipDateLimit = null): mixed
    {
        $data = ['items' => $items];

        if ($shipDateLimit !== null) {
            $data['ship_date_limit'] = $shipDateLimit;
        }

        return $this->cars()->holdCars(new HoldCarsRequest($data));
    }

    /**
     * Release cars for shipping.
     *
     * @param  array<int, string>  $items  Array of chassis numbers
     */
    public function releaseCars(array $items): mixed
    {
        return $this->cars()->releaseCars(new ReleaseCarsRequest(['items' => $items]));
    }

    /**
     * Withhold cars upon arrival.
     *
     * @param  array<int, string>  $items  Array of chassis numbers
     * @param  string|null  $reason  Optional reason for withholding
     */
    public function withholdCars(array $items, ?string $reason = null): mixed
    {
        $data = ['items' => $items];

        if ($reason !== null) {
            $data['reason'] = $reason;
        }

        return $this->cars()->withholdCars(new WithholdCarsRequest($data));
    }

    /**
     * Grant cars (clear withhold status).
     *
     * @param  array<int, string>  $items  Array of chassis numbers
     */
    public function grantCars(array $items): mixed
    {
        return $this->cars()->grantCars(new GrantCarsRequest(['items' => $items]));
    }

    /**
     * Set yard ETA for cars.
     *
     * @param  array<int, array<string, string>>  $items  Array of items with 'chassis' and 'eta' keys (e.g., [['chassis' => 'ABC123', 'eta' => '2026-02-15'], ['chassis' => 'DEF456', 'eta' => '2026-02-16']])
     */
    public function setYardEta(array $items): mixed
    {
        return $this->cars()->setYardEta(new SetYardEtaRequest(['items' => $items]));
    }

    /**
     * Store car photos from URLs.
     *
     * @param  array<int, mixed>  $photos  Array of photo data with chassis, album, and urls
     */
    public function storeCarPhotos(array $photos): mixed
    {
        return $this->carPhotos()->storeCarPhotoUrls($photos);
    }

    /**
     * Store car photos from files.
     *
     * @param  array<int, mixed>  $photos  Array of photo files with chassis and album
     */
    public function storeCarPhotoFiles(array $photos): mixed
    {
        return $this->carPhotos()->storeCarPhotoFiles($photos);
    }

    /**
     * Store car documents from URLs.
     *
     * @param  array<int, mixed>  $documents  Array of document data
     */
    public function storeCarDocuments(array $documents): mixed
    {
        return $this->carDocuments()->storeCarDocumentUrls($documents);
    }

    /**
     * Store car documents from files.
     *
     * @param  array<int, mixed>  $documents  Array of document files
     */
    public function storeCarDocumentFiles(array $documents): mixed
    {
        return $this->carDocuments()->storeCarDocumentFiles($documents);
    }

    /*
    |--------------------------------------------------------------------------
    | Voyages API Shortcuts
    |--------------------------------------------------------------------------
    */

    /**
     * Get paginated list of voyages.
     *
     * @param  array<string, mixed>  $params  Optional query parameters
     */
    public function getVoyages(array $params = []): mixed
    {
        return $this->voyages()->getVoyages(
            $params['sort'] ?? null,
            $params['per_page'] ?? null,
            $params['page'] ?? null,
        );
    }

    /**
     * Get a specific voyage by identifier.
     */
    public function getVoyage(int $voyage): mixed
    {
        return $this->voyages()->getVoyage($voyage);
    }

    /*
    |--------------------------------------------------------------------------
    | Webhooks API Shortcuts
    |--------------------------------------------------------------------------
    */

    /**
     * Get list of webhooks.
     */
    public function getWebhooks(): array
    {
        return $this->webhooks()->getWebhooks();
    }

    /**
     * Get a specific webhook by ID.
     */
    public function getWebhook(string $name): Webhook
    {
        return $this->webhooks()->getWebhook($name);
    }

    /**
     * Create a new webhook.
     *
     * @param  Webhook|array<string, mixed>  $data  Webhook data or Webhook model
     */
    public function storeWebhook(Webhook|array $data): Webhook
    {
        if (is_array($data)) {
            $data = new Webhook($data);
        }

        return $this->webhooks()->storeWebhook($data);
    }

    /**
     * Update an existing webhook.
     *
     * @param  Webhook|array<string, mixed>  $data  Webhook data or Webhook model
     */
    public function updateWebhook(string $name, Webhook|array $data): Webhook
    {
        if (is_array($data)) {
            $data = new Webhook($data);
        }

        return $this->webhooks()->updateWebhook($name, $data);
    }

    /**
     * Delete a webhook.
     */
    public function deleteWebhook(string $name): \Dinas\ShippingSdk\Model\ActionResponse
    {
        return $this->webhooks()->deleteWebhook($name);
    }

    /**
     * Toggle the active status of a webhook.
     */
    public function toggleWebhook(string $name): \Dinas\ShippingSdk\Model\ActionResponse
    {
        return $this->webhooks()->toggleWebhook($name);
    }

    /**
     * Send a test payload to the webhook.
     */
    public function testWebhook(string $name): \Dinas\ShippingSdk\Model\ActionResponse
    {
        return $this->webhooks()->testWebhook($name);
    }
}
