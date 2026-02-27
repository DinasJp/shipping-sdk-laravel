<?php

declare(strict_types=1);

namespace Dinas\Shipping\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShippingJobResolved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param string $jobId The API job ID
     * @param int|null $userId The user who initiated the request
     * @param string $method The method that was called
     * @param string $status Job status: 'finished' or 'failed'
     * @param string|null $message Optional message from the API
     * @param array<int, array{chassis?: string, error?: string}> $errors Errors from initial response
     */
    public function __construct(
        public string  $jobId,
        public ?int    $userId,
        public string  $method,
        public string  $status,
        public ?string $message,
        public array   $errors = [],
    )
    {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        if ($this->userId) {
            return [new PrivateChannel("App.Models.User.$this->userId")];
        }

        return [];
    }

    public function broadcastAs(): string
    {
        return 'shipping.job.resolved';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'jobId' => $this->jobId,
            'method' => $this->method,
            'status' => $this->status,
            'message' => $this->message,
            'errors' => $this->errors,
        ];
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        return config('dinas-shipping-sdk.webhook_jobs.broadcasting.enabled', false)
            && $this->userId !== null;
    }
}
