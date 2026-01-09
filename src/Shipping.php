<?php

namespace Dinas\Shipping;

use Dinas\ShippingSdk\Api\CarsApi;
use Dinas\ShippingSdk\Api\VoyagesApi;
use Dinas\ShippingSdk\Api\WebhooksApi;
use Dinas\ShippingSdk\Configuration;
use Dinas\ShippingSdk\Model\Webhook;
use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;

class Shipping
{
    protected Configuration $configuration;

    protected ClientInterface $httpClient;

    protected ?CarsApi $carsApi = null;

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
     *                                        - voyage: Filter by voyage
     *                                        - photos: Filter by photos presence
     *                                        - on_yard: Filter by yard presence
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
            $params['voyage'] ?? null,
            $params['photos'] ?? null,
            $params['on_yard'] ?? null,
            $params['sort'] ?? null,
            $params['per_page'] ?? null,
            $params['page'] ?? null,
        );
    }

    /**
     * Get car photos.
     *
     * @param  array<string, mixed>  $params  Optional query parameters (same as getCars)
     */
    public function getCarPhotos(array $params = []): mixed
    {
        return $this->cars()->getCarPhotos(
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
     * Store car photos.
     *
     * @param  array<int, mixed>  $photos  Array of photo data
     */
    public function storeCarPhotos(array $photos): mixed
    {
        return $this->cars()->storeCarPhotos($photos);
    }

    /**
     * Store car documents.
     *
     * @param  array<int, mixed>  $documents  Array of document data
     */
    public function storeCarDocuments(array $documents): mixed
    {
        if (empty($documents)) {
            return [];
        }

        $responses = [];
        foreach (array_chunk($documents, 20) as $chunk) {
            $responses[] = $this->cars()->storeCarDocuments($chunk);
        }

        return array_last($responses);
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
    public function getVoyage(string $voyage): mixed
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
