<?php

declare(strict_types=1);

namespace Dinas\Shipping\DTOs;

readonly class WebhookJobContext
{
    /**
     * @param string $jobId The API job ID
     * @param int|null $userId The user who initiated the request
     * @param string $method The method that was called (e.g. storeCarPhotos)
     * @param string $status Job status: 'finished' or 'failed'
     * @param string|null $message Optional message from the API
     * @param array<int, array{chassis?: string, error?: string}> $errors Errors from initial API response
     * @param array<string, mixed> $webhookPayload Full webhook payload
     */
    public function __construct(
        public string  $jobId,
        public ?int    $userId,
        public string  $method,
        public string  $status,
        public ?string $message,
        public array   $errors,
        public array   $webhookPayload,
    )
    {
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
