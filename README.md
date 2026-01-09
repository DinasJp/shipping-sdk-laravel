# Laravel client for Dinas Shipping API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dinas/shipping-sdk-laravel.svg?style=flat-square)](https://packagist.org/packages/dinas/shipping-sdk-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/dinas/shipping-sdk-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/dinasjp/shipping-sdk-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/dinas/shipping-sdk-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/dinasjp/shipping-sdk-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/dinas/shipping-sdk-laravel.svg?style=flat-square)](https://packagist.org/packages/dinas/shipping-sdk-laravel)

This package is two-way bridge between Laravel applications and the Dinas Shipping API.
You can send data to REST endpoints and handle incoming webhooks with ease.

Incoming events are automatically verified, logged, and dispatched as Laravel jobs, enabling smooth
asynchronous updates such as shipment status changes or document availability.

You can find the full documentation [here](https://shipping.dinas.jp/api/documentation).

## Installation

You can install the package via composer:

```bash
composer require dinas/shipping-sdk-laravel
```

The service provider will automatically register itself.

Add the following environment variables to your `.env` file:

```dotenv
DINAS_SHIPPING_TOKEN=your-api-token-here
DINAS_SHIPPING_SECRET=your-random-webhook-secret-here
```
You can obtain your API token from the dashboard https://shipping.dinas.jp/settings/tokens.

### Webhook Setup

First, add the webhook route to your `api` or `web` route file:

```php
Route::dinasShippingWebhooks('dinas-shipping/webhook');
```

You can customize the endpoint path as needed, it will be automatically discovered on setup command.

Behind the scenes, by default this will register a POST route to a controller provided by this package.
Because the app that sends webhooks to you has no way of getting a csrf-token for `web`, you must exclude the route from csrf token validation.

Here is how you can do that in recent versions of Laravel.

```php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
       // ...
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'dinas-shipping/webhook'
        ]);
    })->create();
```

Next, publish the webhook migration and run it:

```bash
php artisan vendor:publish --provider="Spatie\WebhookClient\WebhookClientServiceProvider" --tag="webhook-client-migrations"
php artisan migrate
```

Now, register the webhook with Dinas Shipping API by running:

```bash
php artisan webhook:dinas-shipping -i
```

You can check status of all webhooks with:

```bash
php artisan webhook:dinas-shipping
```

To remove the webhook registration, run:

```bash
php artisan webhook:dinas-shipping -r
```

More information is accessible with:

```bash
php artisan webhook:dinas-shipping --help
```

You can publish the config files with:

```bash
php artisan vendor:publish --tag="shipping-sdk-laravel-config"
```

## Usage

### Using the Facade

```php
use Dinas\Shipping\Facades\Shipping;

// Get pending cars 
$cars = Shipping::getCars([
    'status' => 'pending',
]);

// Get cars with optional filters
$cars = Shipping::getCars([
    'status' => 'pending',
    'search' => 'Toyota',
    'per_page' => 50,
    'page' => 1,
]);

// Get car photos
$photos = Shipping::getCarPhotos(['voyage' => 'VES0001PORT']);

// Sync cars
$result = Shipping::syncCars([
    ['chassis' => 'ABC123', 'make' => 'Toyota', 'model' => 'Camry'],
    ['chassis' => 'DEF456', 'make' => 'Honda', 'model' => 'Civic'],
]);

// Store car photos
Shipping::storeCarPhotos($photoData);

// Store car documents
Shipping::storeCarDocuments($documentData);
```

### Voyages API

```php
use Dinas\Shipping\Facades\Shipping;

// Get all voyages
$voyages = Shipping::getVoyages([
    'per_page' => 25,
    'page' => 1,
]);

// Get a specific voyage
$voyage = Shipping::getVoyage('VES0001PORT');
```

### Direct API Access

For full control, you can access the underlying API instances directly:

```php
use Dinas\Shipping\Facades\Shipping;

// Access Cars API directly
$carsApi = Shipping::cars();
$result = $carsApi->getCars(status: 'pending', perPage: 100);

// Access Voyages API directly
$voyagesApi = Shipping::voyages();
$voyages = $voyagesApi->getVoyages();

// Access Webhooks API directly
$webhooksApi = Shipping::webhooks();
$webhooks = $webhooksApi->getWebhooks();

// Get the SDK configuration
$config = Shipping::getConfiguration();

// Set a custom HTTP client
Shipping::setHttpClient($customClient);
```

### Dependency Injection

You can also inject the `Shipping` class directly:

```php
use Dinas\Shipping\Shipping;

class CarController extends Controller
{
    public function __construct(
        protected Shipping $shipping
    ) {}

    public function index()
    {
        return $this->shipping->getCars(['status' => 'pending']);
    }
}
```

### Handling webhook requests using jobs

If you want to do something when a specific event type comes in you can define a job that does the work. Here's an example of such a job:

```php
<?php

namespace App\Jobs\DinasWebhooks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\WebhookClient\Models\WebhookCall;

class TestJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public WebhookCall $webhookCall)
    {
    }

    public function handle(): void
    {
        $payload = $this->webhookCall->payload;
        
        // do your work here
    }
}
```

We highly recommend that you make this job queueable, because this will minimize the response time of the webhook requests.
This allows you to handle more webhook requests and avoid timeouts.

After having created your job you must register it at the `jobs` array in the `dinas-shipping-sdk.php` config file.

```php
// config/dinas-shipping-sdk.php

'jobs' => [
    'car.updated' => \App\Jobs\DinasWebhooks\HandleCarUpdated::class,
],
```

You can find the [full list of events types](https://shipping.dinas.jp/api/events) in the documentation.


In case you want to configure one job as default to process all undefined event, you may set the job at `default_job` in
the `dinas-shipping-sdk.php` config file. The value should be the fully qualified classname.


## Advanced usage

### Retry handling a webhook

All incoming webhook requests are written to the database. This is incredibly valuable when something goes wrong while
handling a webhook call. You can easily retry processing the webhook call, after you've investigated and fixed
the cause of failure, like this:

```php
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

dispatch(new ProcessWebhookJob(WebhookCall::find($id)));
```

### More Information
For more information on how to use the underlying SDK, please refer to the [spatie webhook client](https://github.com/spatie/laravel-webhook-client)

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Dinas](https://github.com/dinasjp)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
