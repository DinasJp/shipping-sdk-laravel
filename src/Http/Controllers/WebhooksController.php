<?php

declare(strict_types=1);

namespace Dinas\Shipping\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\WebhookClient\Exceptions\InvalidConfig;
use Spatie\WebhookClient\SignatureValidator\DefaultSignatureValidator;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookProcessor;

class WebhooksController
{
    /**
     * @throws InvalidConfig
     */
    public function __invoke(Request $request)
    {
        $webhookConfig = new WebhookConfig([
            'name' => 'DinasShipping',
            'signing_secret' => config('dinas-shipping-sdk.webhook.signing_secret'),
            'signature_header_name' => 'Signature',
            'signature_validator' => DefaultSignatureValidator::class,
            'webhook_profile' => config('dinas-shipping-sdk.webhook.profile'),
            'webhook_model' => config('dinas-shipping-sdk.webhook.model'),
            'process_webhook_job' => config('dinas-shipping-sdk.webhook.default_job'),
        ]);

        return (new WebhookProcessor($request, $webhookConfig))->process();
    }
}
