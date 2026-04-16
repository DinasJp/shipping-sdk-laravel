<?php

declare(strict_types=1);

namespace Dinas\Shipping\Exceptions;

use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Spatie\WebhookClient\Models\WebhookCall;

class WebhookFailed extends Exception
{
    public static function jobClassDoesNotExist(string $jobClass, WebhookCall $webhookCall): self
    {
        return new self("Could not process webhook id `{$webhookCall->id}` of type `{$webhookCall->payload['event']} because the configured jobclass `$jobClass` does not exist.");
    }

    public static function missingType(WebhookCall $webhookCall): self
    {
        return new self("Webhook call id `{$webhookCall->id}` did not contain a event. Valid webhook calls should always contain a event.");
    }

    public function render($request): Response|ResponseFactory
    {
        return response(['error' => $this->getMessage()], 400);
    }
}
