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

    'webhook_jobs' => [
        /*
         * The integer amount of days after which models should be deleted.
         *
         * It deletes all records after 30 days. Set to null if no models should be deleted.
         */
        'delete_after_days' => 30,

        /*
         * When enabled, a ShippingJobResolved event will be broadcast on a private
         * channel when an API job completes via webhook. This allows real-time
         * notifications in the frontend. Requires Laravel Broadcasting to be
         * configured (e.g. with Pusher).
         *
         * Channel format: App.Models.User.{userId}
         */
        'broadcasting' => [
            'enabled' => env('DINAS_SHIPPING_BROADCASTING', true),
        ],
    ],

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
