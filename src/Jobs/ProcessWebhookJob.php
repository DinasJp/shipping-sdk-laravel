<?php

declare(strict_types=1);

namespace Dinas\Shipping\Jobs;

use Dinas\Shipping\Exceptions\WebhookFailed;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as BaseProcessWebhookJob;
use Spatie\WebhookClient\Models\WebhookCall;

class ProcessWebhookJob extends BaseProcessWebhookJob
{
    public function __construct(WebhookCall $webhookCall)
    {
        parent::__construct($webhookCall);
        $this->onConnection(config('dinas-shipping-sdk.webhook.connection'));
        $this->onQueue(config('dinas-shipping-sdk.webhook.queue'));
    }

    /**
     * @throws WebhookFailed
     */
    public function handle(): void
    {
        if (! isset($this->webhookCall->payload['event']) || $this->webhookCall->payload['event'] === '') {
            throw WebhookFailed::missingType($this->webhookCall);
        }

        $type = $this->webhookCall->payload['event'];

        event("dinas-shipping::{$type}", $this->webhookCall);

        collect(config('dinas-shipping-sdk.webhook.jobs'))
            ->filter(function (string $jobClassName, $eventActionName) use ($type) {
                if ($eventActionName === '*') {
                    return true;
                }

                return $eventActionName === $type;
            })
            ->each(function (string $jobClassName) {
                if (! class_exists($jobClassName)) {
                    throw WebhookFailed::jobClassDoesNotExist($jobClassName, $this->webhookCall);
                }
            })
            ->each(fn (string $jobClassName) => dispatch(new $jobClassName($this->webhookCall)));
    }
}
