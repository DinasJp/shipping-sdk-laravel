<?php

declare(strict_types=1);

namespace Dinas\Shipping\Jobs;

use Dinas\Shipping\DTOs\WebhookJobContext;
use Dinas\Shipping\Events\ShippingJobResolved;
use Dinas\Shipping\Exceptions\WebhookFailed;
use Dinas\Shipping\Models\WebhookJob;
use Illuminate\Support\Facades\Log;
use Laravel\SerializableClosure\SerializableClosure;
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

        event("dinas-shipping::$type", $this->webhookCall);

        if ($type === 'api.job') {
            $this->resolveWebhookJob();
        }

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

    protected function resolveWebhookJob(): void
    {
        $payload = $this->webhookCall->payload;
        $jobId = $payload['jobId'] ?? null;

        if (! $jobId) {
            return;
        }

        $webhookJobs = WebhookJob::forJob($jobId)->pending()->get();

        foreach ($webhookJobs as $webhookJob) {
            /** @var WebhookJob $webhookJob */
            if (! $webhookJob->claim()) {
                continue;
            }

            try {
                $webhookJob->update(['payload' => $payload]);

                if ($webhookJob->callable) {
                    /** @var SerializableClosure $serialized */
                    $serialized = unserialize($webhookJob->callable);
                    $callback = $serialized->getClosure();

                    $context = new WebhookJobContext(
                        jobId: $jobId,
                        userId: $webhookJob->user_id,
                        method: $webhookJob->method,
                        status: $payload['status'] ?? 'unknown',
                        message: $payload['message'] ?? null,
                        errors: $webhookJob->errors ?? [],
                        webhookPayload: $payload,
                    );

                    $callback($context);
                }

                $webhookJob->markCompleted();
            } catch (\Throwable $e) {
                $webhookJob->markFailed();
                Log::error('Shipping webhook job callback failed', [
                    'job_id' => $jobId,
                    'error' => $e->getMessage(),
                ]);
            }

            event(new ShippingJobResolved(
                jobId: $jobId,
                userId: $webhookJob->user_id,
                method: $webhookJob->method,
                status: $payload['status'] ?? 'unknown',
                message: $payload['message'] ?? null,
                errors: $webhookJob->errors ?? [],
            ));
        }
    }
}
