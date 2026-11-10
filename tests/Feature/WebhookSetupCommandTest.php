<?php

declare(strict_types=1);

use Dinas\Shipping\Facades\Shipping;
use Dinas\ShippingSdk\ApiException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

it('shows why the API refused the webhook', function () {
    config([
        'dinas-shipping-sdk.token' => 'test-token',
        'dinas-shipping-sdk.webhook.signing_secret' => 'test-secret',
    ]);

    Shipping::shouldReceive('storeWebhook')->andThrow(new ApiException(
        '[403] Invalid ability provided.',
        new Request('POST', 'https://shipping.test/api/webhooks'),
        new Response(403),
    ));

    $this->artisan('webhook:dinas-shipping', ['--install' => true, '--url' => 'https://app.test/hook'])
        ->expectsOutputToContain("Could not add webhook 'default': [403] Invalid ability provided.")
        ->assertFailed();
});
