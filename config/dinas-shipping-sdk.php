<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dinas Shipping API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Dinas Shipping API credentials and settings here.
    | You can obtain your API token from the dashboard https://shipping.dinas.jp/settings/tokens.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API Access Token
    |--------------------------------------------------------------------------
    |
    | The Bearer token used to authenticate with the Dinas Shipping API.
    |
    */
    'token' => env('DINAS_SHIPPING_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Dinas Shipping API endpoints.
    |
    */
    'base_url' => env('DINAS_SHIPPING_BASE_URL', 'https://shipping.dinas.jp'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for API requests.
    |
    */
    'timeout' => env('DINAS_SHIPPING_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable debug mode for additional logging of API requests/responses.
    |
    */
    'debug' => env('DINAS_SHIPPING_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | We expect that every webhook call will be signed using your secret. This secret
    | is used to verify that the payload has not been tampered with.
    |
    */
    'webhook' => [
        'signing_secret' => env('DINAS_SHIPPING_SECRET'),

        /*
         * This class determines if the webhook call should be stored and processed.
         */
        'profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,

        /*
         * The classname of the model to be used to store webhook calls. The class should
         * be equal or extend Spatie\WebhookClient\Models\WebhookCall.
         */
        'model' => \Spatie\WebhookClient\Models\WebhookCall::class,

        /*
         * Current job dispatches new jobs listed in next configuration based on event type.
         * You can re-define this to process all events in one place
         */
        'default_job' => \Dinas\Shipping\Jobs\ProcessWebhookJob::class,

        /*
         * You can define the job that should be run when a certain webhook hits your application
         * here. The key is the name of the Dinas shipping event.
         *
         * You can find a list of webhook events here:
         * https://shipping.dinas.jp/api/documentation.
         */
        'jobs' => [
            // 'car.updated' => \App\Jobs\DinasWebhooks\HandleCarUpdated::class,
            // 'voyage.departed' => \App\Jobs\DinasWebhooks\HandleVoyageDeparted::class,
            // 'invoice.billed' => \App\Jobs\DinasWebhooks\HandleInvoiceBilled::class,
            // '*' => \App\Jobs\DinasWebhooks\HandleAllWebhooks::class
        ],

        /*
         * Specify a connection and or a queue to process the webhooks
         */
        'connection' => env('DINAS_SHIPPING_CONNECTION'),
        'queue' => env('DINAS_SHIPPING_QUEUE'),
    ],
];
