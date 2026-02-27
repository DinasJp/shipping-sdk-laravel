<?php

declare(strict_types=1);

namespace Dinas\Shipping\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Spatie\WebhookClient\Exceptions\InvalidConfig;

/**
 * @property int $id
 * @property string $job_id
 * @property int|null $user_id
 * @property string $method
 * @property string|null $callable
 * @property array|null $payload
 * @property array|null $errors
 * @property string $status
 * @property Carbon|null $resolved_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class WebhookJob extends Model
{
    use MassPrunable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'job_id',
        'user_id',
        'method',
        'callable',
        'payload',
        'errors',
        'status',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'errors' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeForJob(Builder $query, string $jobId): Builder
    {
        return $query->where('job_id', $jobId);
    }

    /**
     * Atomically claim this job for processing. Returns true if claimed successfully.
     */
    public function claim(): bool
    {
        return self::where('id', $this->id)
                ->where('status', self::STATUS_PENDING)
                ->update([
                    'status' => self::STATUS_PROCESSING,
                ]) > 0;
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'resolved_at' => now(),
        ]);
    }

    public function markFailed(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'resolved_at' => now(),
        ]);
    }

    /**
     * @throws InvalidConfig
     */
    public function prunable(): Builder
    {
        $days = config('dinas-shipping-sdk.webhook_jobs.delete_after_days');

        if (!is_int($days)) {
            throw InvalidConfig::invalidPrunable($days);
        }

        return static::where('resolved_at', '<', now()->subDays($days));
    }
}
