<?php

declare(strict_types=1);

namespace Dinas\Shipping\Exceptions;

use Exception;
use Spatie\WebhookClient\Models\WebhookCall;

class WebhookFailed extends Exception
{
    public static function jobClassDoesNotExist(string $jobClass, WebhookCall $webhookCall): self
    {
        return new static("Could not process webhook id `{$webhookCall->id}` of type `{$webhookCall->payload['event']} because the configured jobclass `$jobClass` does not exist.");
    }

    public static function missingType(WebhookCall $webhookCall): self
    {
        return new static("Webhook call id `{$webhookCall->id}` did not contain a event. Valid webhook calls should always contain a event.");
    }

    public function render($request): \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
    {
        return response(['error' => $this->getMessage()], 400);
    }
}
