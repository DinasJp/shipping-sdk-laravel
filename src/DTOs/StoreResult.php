<?php

declare(strict_types=1);

namespace Dinas\Shipping\DTOs;

readonly class StoreResult
{
    /**
     * @param  bool  $ok  Whether all chunks succeeded without API exceptions
     * @param  array<int, string>  $jobIds  Job IDs from API responses
     * @param  array<int, array{chassis?: string, error?: string}>  $errors  Structured errors from API responses (car not found, etc.)
     * @param  array<int, array<string, mixed>>  $validationErrors  Validation errors from API (keyed by field)
     * @param  array<int, mixed>  $responses  Raw API responses per chunk
     */
    public function __construct(
        public bool $ok,
        public array $jobIds = [],
        public array $errors = [],
        public array $validationErrors = [],
        public array $responses = [],
    ) {}

    public function hasErrors(): bool
    {
        return count($this->errors) > 0 || count($this->validationErrors) > 0;
    }

    public function hasValidationErrors(): bool
    {
        return count($this->validationErrors) > 0;
    }

    /**
     * Get all errors as a flat array of strings.
     *
     * @return array<int, string>
     */
    public function allErrorMessages(): array
    {
        $messages = [];

        foreach ($this->errors as $error) {
            $msg = $error['error'] ?? 'Unknown error';
            if (isset($error['chassis'])) {
                $messages[] = "[{$error['chassis']}] $msg";
            } else {
                $messages[] = $msg;
            }
        }

        foreach ($this->validationErrors as $field => $fieldErrors) {
            if (is_array($fieldErrors)) {
                foreach ($fieldErrors as $fieldError) {
                    $messages[] = is_string($field) ? "[$field] $fieldError" : (string) $fieldError;
                }
            } else {
                $messages[] = (string) $fieldErrors;
            }
        }

        return $messages;
    }
}
