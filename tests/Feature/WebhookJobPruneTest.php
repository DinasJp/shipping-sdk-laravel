<?php

declare(strict_types=1);

use Dinas\Shipping\Models\WebhookJob;

function webhookJobCreatedDaysAgo(int $days, string $status): WebhookJob
{
    $job = WebhookJob::create(['job_id' => "job-$days-$status", 'method' => 'storeCarPhotos', 'status' => $status]);
    $job->forceFill(['created_at' => now()->subDays($days)])->save();

    return $job;
}

it('prunes old jobs, also those whose webhook never came', function () {
    config(['dinas-shipping-sdk.webhook_jobs.delete_after_days' => 30]);

    $stuck = webhookJobCreatedDaysAgo(40, WebhookJob::STATUS_PENDING);
    $done = webhookJobCreatedDaysAgo(40, WebhookJob::STATUS_COMPLETED);
    webhookJobCreatedDaysAgo(5, WebhookJob::STATUS_PENDING);

    expect((new WebhookJob)->prunable()->pluck('id')->sort()->values()->all())->toBe([$stuck->id, $done->id]);
});

it('prunes nothing when the days are null', function () {
    config(['dinas-shipping-sdk.webhook_jobs.delete_after_days' => null]);

    webhookJobCreatedDaysAgo(400, WebhookJob::STATUS_COMPLETED);

    expect((new WebhookJob)->prunable()->count())->toBe(0);
});
