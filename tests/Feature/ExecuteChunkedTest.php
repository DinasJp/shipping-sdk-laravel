<?php

declare(strict_types=1);

use Dinas\Shipping\DTOs\StoreResult;
use Dinas\Shipping\DTOs\WebhookJobContext;
use Dinas\Shipping\Events\ShippingJobResolved;
use Dinas\Shipping\Jobs\ProcessWebhookJob;
use Dinas\Shipping\Models\WebhookJob;
use Dinas\Shipping\Shipping;
use Dinas\ShippingSdk\Api\CarDocumentsApi;
use Dinas\ShippingSdk\Api\CarPhotosApi;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookClient\Models\WebhookCall;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function createShippingWithMockedPhotosApi(CarPhotosApi $mock): Shipping
{
    $shipping = new Shipping;
    $ref = new ReflectionClass($shipping);
    $prop = $ref->getProperty('carPhotosApi');
    $prop->setValue($shipping, $mock);

    return $shipping;
}

function createShippingWithMockedDocsApi(CarDocumentsApi $mock): Shipping
{
    $shipping = new Shipping;
    $ref = new ReflectionClass($shipping);
    $prop = $ref->getProperty('carDocumentsApi');
    $prop->setValue($shipping, $mock);

    return $shipping;
}

function mockPhotosApi(string $jobId = 'job-photo-1'): CarPhotosApi
{
    $api = Mockery::mock(CarPhotosApi::class);
    $api->shouldReceive('storeCarPhotoUrls')
        ->andReturn(json_encode(['jobId' => $jobId, 'errors' => []]));

    return $api;
}

function mockDocsApi(string $jobId = 'job-doc-1'): CarDocumentsApi
{
    $api = Mockery::mock(CarDocumentsApi::class);
    $api->shouldReceive('storeCarDocumentUrls')
        ->andReturn(json_encode(['jobId' => $jobId, 'errors' => []]));

    return $api;
}

/**
 * Simulate the webhook arrival for an api.job event and run the ProcessWebhookJob synchronously.
 */
function simulateWebhook(string $jobId, string $status = 'finished', ?string $message = null): void
{
    $webhookCall = WebhookCall::create([
        'name' => 'dinas-shipping',
        'url' => 'https://localhost/dinas-shipping/webhook',
        'payload' => [
            'event' => 'api.job',
            'jobId' => $jobId,
            'status' => $status,
            'message' => $message,
        ],
    ]);

    $job = new ProcessWebhookJob($webhookCall);
    $job->handle();
}

// A named function for testing callable-by-name
function test_named_handler(WebhookJobContext $ctx): void
{
    // We write a marker into the cache so the test can verify it ran
    cache()->put("named_handler_{$ctx->jobId}", $ctx->status, 60);
}

// An invokable class for testing __invoke callables
class InvokableHandler
{
    public function __invoke(WebhookJobContext $ctx): void
    {
        cache()->put("invokable_{$ctx->jobId}", $ctx->method, 60);
    }
}

// A class with a static method for testing [Class, 'method'] callables
class StaticMethodHandler
{
    public static function handle(WebhookJobContext $ctx): void
    {
        cache()->put("static_{$ctx->jobId}", $ctx->userId, 60);
    }
}

// ---------------------------------------------------------------------------
// Setup: run migrations before each test
// ---------------------------------------------------------------------------

beforeEach(function () {
    config([
        'dinas-shipping-sdk.token' => 'test-token',
        'dinas-shipping-sdk.base_url' => 'https://test.example.com',
        'dinas-shipping-sdk.timeout' => 30,
        'dinas-shipping-sdk.debug' => false,
        'dinas-shipping-sdk.webhook.jobs' => [],
        'dinas-shipping-sdk.webhook_jobs.broadcasting.enabled' => true,
    ]);
});

// ---------------------------------------------------------------------------
// Tests: executeChunked via storeCarPhotos
// ---------------------------------------------------------------------------

describe('executeChunked via storeCarPhotos', function () {

    it('stores WebhookJob with closure callback and executes it on webhook', function () {
        $shipping = createShippingWithMockedPhotosApi(mockPhotosApi('job-p-1'));

        $result = $shipping->storeCarPhotos(
            [['chassis' => 'AAA', 'album' => 'exterior', 'urls' => ['https://img.test/1.jpg']]],
            onResolve: function (WebhookJobContext $ctx) {
                cache()->put("closure_{$ctx->jobId}", [
                    'method' => $ctx->method,
                    'status' => $ctx->status,
                    'finished' => $ctx->isFinished(),
                ], 60);
            },
        );

        expect($result)->toBeInstanceOf(StoreResult::class)
            ->and($result->jobIds)->toBe(['job-p-1']);

        // A WebhookJob record should exist
        expect(WebhookJob::count())->toBe(1);
        $wj = WebhookJob::first();
        expect($wj->job_id)->toBe('job-p-1')
            ->and($wj->method)->toBe('storeCarPhotos')
            ->and($wj->status)->toBe(WebhookJob::STATUS_PENDING);

        // Simulate webhook
        Event::fake([ShippingJobResolved::class]);
        simulateWebhook('job-p-1', 'finished');

        $cached = cache()->get('closure_job-p-1');
        expect($cached)->not->toBeNull()
            ->and($cached['method'])->toBe('storeCarPhotos')
            ->and($cached['status'])->toBe('finished')
            ->and($cached['finished'])->toBeTrue();

        // WebhookJob should be marked completed
        $wj->refresh();
        expect($wj->status)->toBe(WebhookJob::STATUS_COMPLETED);
    });

    it('stores WebhookJob with named function callback', function () {
        $shipping = createShippingWithMockedPhotosApi(mockPhotosApi('job-p-named'));

        $shipping->storeCarPhotos(
            [['chassis' => 'BBB', 'album' => 'interior', 'urls' => ['https://img.test/2.jpg']]],
            onResolve: 'test_named_handler',
        );

        expect(WebhookJob::count())->toBe(1);

        Event::fake([ShippingJobResolved::class]);
        simulateWebhook('job-p-named', 'finished');

        expect(cache()->get('named_handler_job-p-named'))->toBe('finished');

        $wj = WebhookJob::first();
        $wj->refresh();
        expect($wj->status)->toBe(WebhookJob::STATUS_COMPLETED);
    });

    it('stores WebhookJob with static class method callback', function () {
        // Fake an authenticated user
        $user = new User;
        $user->id = 42;
        $this->actingAs($user);

        $shipping = createShippingWithMockedPhotosApi(mockPhotosApi('job-p-static'));

        $shipping->storeCarPhotos(
            [['chassis' => 'CCC', 'album' => 'exterior', 'urls' => ['https://img.test/3.jpg']]],
            onResolve: [StaticMethodHandler::class, 'handle'],
        );

        expect(WebhookJob::first()->user_id)->toBe(42);

        Event::fake([ShippingJobResolved::class]);
        simulateWebhook('job-p-static', 'finished');

        expect(cache()->get('static_job-p-static'))->toBe(42);
    });

    it('stores WebhookJob with invokable class callback', function () {
        $shipping = createShippingWithMockedPhotosApi(mockPhotosApi('job-p-invokable'));

        $shipping->storeCarPhotos(
            [['chassis' => 'DDD', 'album' => 'exterior', 'urls' => ['https://img.test/4.jpg']]],
            onResolve: new InvokableHandler,
        );

        Event::fake([ShippingJobResolved::class]);
        simulateWebhook('job-p-invokable', 'finished');

        expect(cache()->get('invokable_job-p-invokable'))->toBe('storeCarPhotos');
    });
});

// ---------------------------------------------------------------------------
// Tests: executeChunked via storeCarDocuments
// ---------------------------------------------------------------------------

describe('executeChunked via storeCarDocuments', function () {

    it('stores WebhookJob with closure callback and executes it on webhook', function () {
        $shipping = createShippingWithMockedDocsApi(mockDocsApi('job-d-1'));

        $result = $shipping->storeCarDocuments(
            [['chassis' => 'EEE', 'type' => 'invoice', 'url' => 'https://docs.test/1.pdf']],
            onResolve: function (WebhookJobContext $ctx) {
                cache()->put("closure_{$ctx->jobId}", $ctx->method, 60);
            },
        );

        expect($result->jobIds)->toBe(['job-d-1'])
            ->and(WebhookJob::first()->method)->toBe('storeCarDocuments');

        Event::fake([ShippingJobResolved::class]);
        simulateWebhook('job-d-1', 'finished');

        expect(cache()->get('closure_job-d-1'))->toBe('storeCarDocuments');
    });

    it('stores WebhookJob with named function callback', function () {
        $shipping = createShippingWithMockedDocsApi(mockDocsApi('job-d-named'));

        $shipping->storeCarDocuments(
            [['chassis' => 'FFF', 'type' => 'title', 'url' => 'https://docs.test/2.pdf']],
            onResolve: 'test_named_handler',
        );

        Event::fake([ShippingJobResolved::class]);
        simulateWebhook('job-d-named', 'failed', 'Something went wrong');

        expect(cache()->get('named_handler_job-d-named'))->toBe('failed');
    });

    it('stores WebhookJob with invokable class callback', function () {
        $shipping = createShippingWithMockedDocsApi(mockDocsApi('job-d-invokable'));

        $shipping->storeCarDocuments(
            [['chassis' => 'GGG', 'type' => 'export', 'url' => 'https://docs.test/3.pdf']],
            onResolve: new InvokableHandler,
        );

        Event::fake([ShippingJobResolved::class]);
        simulateWebhook('job-d-invokable', 'finished');

        expect(cache()->get('invokable_job-d-invokable'))->toBe('storeCarDocuments');
    });

    it('stores WebhookJob with static class method callback', function () {
        $user = new User;
        $user->id = 99;
        $this->actingAs($user);

        $shipping = createShippingWithMockedDocsApi(mockDocsApi('job-d-static'));

        $shipping->storeCarDocuments(
            [['chassis' => 'HHH', 'type' => 'invoice', 'url' => 'https://docs.test/4.pdf']],
            onResolve: [StaticMethodHandler::class, 'handle'],
        );

        Event::fake([ShippingJobResolved::class]);
        simulateWebhook('job-d-static', 'finished');

        expect(cache()->get('static_job-d-static'))->toBe(99);
    });
});

// ---------------------------------------------------------------------------
// Tests: no callback → no WebhookJob records
// ---------------------------------------------------------------------------

describe('executeChunked without onResolve', function () {

    it('does not create WebhookJob when onResolve is null (photos)', function () {
        $shipping = createShippingWithMockedPhotosApi(mockPhotosApi('job-no-cb'));

        $result = $shipping->storeCarPhotos(
            [['chassis' => 'III', 'album' => 'exterior', 'urls' => ['https://img.test/5.jpg']]],
        );

        expect($result->jobIds)->toBe(['job-no-cb'])
            ->and(WebhookJob::count())->toBe(0);
    });

    it('does not create WebhookJob when onResolve is null (documents)', function () {
        $shipping = createShippingWithMockedDocsApi(mockDocsApi('job-no-cb-d'));

        $result = $shipping->storeCarDocuments(
            [['chassis' => 'JJJ', 'type' => 'invoice', 'url' => 'https://docs.test/5.pdf']],
        );

        expect($result->jobIds)->toBe(['job-no-cb-d'])
            ->and(WebhookJob::count())->toBe(0);
    });
});

// ---------------------------------------------------------------------------
// Tests: chunking creates multiple WebhookJob records
// ---------------------------------------------------------------------------

describe('executeChunked chunking behavior', function () {

    it('creates multiple WebhookJob records for large payloads', function () {
        // Generate 150 items → should be split into 2 chunks (100 + 50)
        $items = [];
        for ($i = 0; $i < 150; $i++) {
            $items[] = ['chassis' => "CHUNK$i", 'album' => 'exterior', 'urls' => ["https://img.test/$i.jpg"]];
        }

        $chunkIndex = 0;
        $api = Mockery::mock(CarPhotosApi::class);
        $api->shouldReceive('storeCarPhotoUrls')
            ->twice()
            ->andReturnUsing(function () use (&$chunkIndex) {
                $chunkIndex++;

                return json_encode(['jobId' => "job-chunk-$chunkIndex", 'errors' => []]);
            });

        $shipping = createShippingWithMockedPhotosApi($api);

        $result = $shipping->storeCarPhotos($items, onResolve: function (WebhookJobContext $ctx) {
            $list = cache()->get('chunk_contexts', []);
            $list[] = $ctx->jobId;
            cache()->put('chunk_contexts', $list, 60);
        });

        expect($result->jobIds)->toBe(['job-chunk-1', 'job-chunk-2'])
            ->and(WebhookJob::count())->toBe(2);

        // Simulate both webhooks
        Event::fake([ShippingJobResolved::class]);
        simulateWebhook('job-chunk-1', 'finished');
        simulateWebhook('job-chunk-2', 'finished');

        $contexts = cache()->get('chunk_contexts', []);
        expect($contexts)->toHaveCount(2)
            ->and($contexts[0])->toBe('job-chunk-1')
            ->and($contexts[1])->toBe('job-chunk-2');
    });
});

// ---------------------------------------------------------------------------
// Tests: failed webhook status
// ---------------------------------------------------------------------------

describe('executeChunked with failed webhook', function () {

    it('passes failed status to callback', function () {
        $shipping = createShippingWithMockedPhotosApi(mockPhotosApi('job-fail'));

        $shipping->storeCarPhotos(
            [['chassis' => 'FAIL1', 'album' => 'exterior', 'urls' => ['https://img.test/fail.jpg']]],
            onResolve: function (WebhookJobContext $ctx) {
                cache()->put("fail_{$ctx->jobId}", [
                    'failed' => $ctx->isFailed(),
                    'message' => $ctx->message,
                ], 60);
            },
        );

        Event::fake([ShippingJobResolved::class]);
        simulateWebhook('job-fail', 'failed', 'Processing error');

        $cached = cache()->get('fail_job-fail');
        expect($cached)->not->toBeNull()
            ->and($cached['failed'])->toBeTrue()
            ->and($cached['message'])->toBe('Processing error');
    });
});

// ---------------------------------------------------------------------------
// Tests: ShippingJobResolved event is dispatched (broadcasting)
// ---------------------------------------------------------------------------

describe('ShippingJobResolved broadcasting', function () {

    it('dispatches ShippingJobResolved event after webhook processing', function () {
        $user = new User;
        $user->id = 7;
        $this->actingAs($user);

        $shipping = createShippingWithMockedPhotosApi(mockPhotosApi('job-event-1'));

        $shipping->storeCarPhotos(
            [['chassis' => 'EVT1', 'album' => 'exterior', 'urls' => ['https://img.test/evt.jpg']]],
            onResolve: function (WebhookJobContext $ctx) {
                // no-op
            },
        );

        Event::fake([ShippingJobResolved::class]);
        simulateWebhook('job-event-1', 'finished');

        Event::assertDispatched(ShippingJobResolved::class, function (ShippingJobResolved $e) {
            return $e->jobId === 'job-event-1'
                && $e->userId === 7
                && $e->method === 'storeCarPhotos'
                && $e->status === 'finished'
                && $e->message === null;
        });
    });

    it('dispatches ShippingJobResolved event for documents', function () {
        $user = new User;
        $user->id = 13;
        $this->actingAs($user);

        $shipping = createShippingWithMockedDocsApi(mockDocsApi('job-event-2'));

        $shipping->storeCarDocuments(
            [['chassis' => 'EVT2', 'type' => 'invoice', 'url' => 'https://docs.test/evt.pdf']],
            onResolve: function (WebhookJobContext $ctx) {
                // no-op
            },
        );

        Event::fake([ShippingJobResolved::class]);
        simulateWebhook('job-event-2', 'failed', 'Timeout');

        Event::assertDispatched(ShippingJobResolved::class, function (ShippingJobResolved $e) {
            return $e->jobId === 'job-event-2'
                && $e->userId === 13
                && $e->method === 'storeCarDocuments'
                && $e->status === 'failed'
                && $e->message === 'Timeout';
        });
    });

    it('dispatches event even without onResolve callback when WebhookJob exists', function () {
        // Manually create a WebhookJob without callable to test event dispatch
        WebhookJob::create([
            'job_id' => 'job-event-3',
            'user_id' => 5,
            'method' => 'storeCarPhotos',
            'callable' => null,
            'errors' => null,
            'status' => WebhookJob::STATUS_PENDING,
        ]);

        Event::fake([ShippingJobResolved::class]);
        simulateWebhook('job-event-3', 'finished');

        Event::assertDispatched(ShippingJobResolved::class, function (ShippingJobResolved $e) {
            return $e->jobId === 'job-event-3'
                && $e->userId === 5
                && $e->status === 'finished';
        });
    });

    it('includes errors in the broadcast event', function () {
        $user = new User;
        $user->id = 20;
        $this->actingAs($user);

        // API returns a response with errors
        $api = Mockery::mock(CarPhotosApi::class);
        $api->shouldReceive('storeCarPhotoUrls')
            ->andReturn(json_encode([
                'jobId' => 'job-event-err',
                'errors' => [['chassis' => 'X1', 'error' => 'Car not found']],
            ]));

        $shipping = createShippingWithMockedPhotosApi($api);

        $shipping->storeCarPhotos(
            [['chassis' => 'X1', 'album' => 'exterior', 'urls' => ['https://img.test/x.jpg']]],
            onResolve: function (WebhookJobContext $ctx) {
                // no-op
            },
        );

        Event::fake([ShippingJobResolved::class]);
        simulateWebhook('job-event-err', 'finished');

        Event::assertDispatched(ShippingJobResolved::class, function (ShippingJobResolved $e) {
            return $e->jobId === 'job-event-err'
                && count($e->errors) === 1
                && $e->errors[0]['chassis'] === 'X1';
        });
    });

    it('broadcasts on the correct private channel', function () {
        $event = new ShippingJobResolved(
            jobId: 'job-bc-1',
            userId: 42,
            method: 'storeCarPhotos',
            status: 'finished',
            message: null,
        );

        $channels = $event->broadcastOn();

        expect($channels)->toHaveCount(1)
            ->and($channels[0])->toBeInstanceOf(PrivateChannel::class)
            ->and($channels[0]->name)->toBe('private-App.Models.User.42');
    });

    it('broadcasts with correct event name', function () {
        $event = new ShippingJobResolved(
            jobId: 'job-bc-2',
            userId: 1,
            method: 'storeCarDocuments',
            status: 'failed',
            message: 'Error',
        );

        expect($event->broadcastAs())->toBe('shipping.job.resolved');
    });

    it('broadcast payload contains expected keys', function () {
        $event = new ShippingJobResolved(
            jobId: 'job-bc-3',
            userId: 1,
            method: 'storeCarDocuments',
            status: 'failed',
            message: 'Timeout',
            errors: [['chassis' => 'Z1', 'error' => 'Not found']],
        );

        $payload = $event->broadcastWith();

        expect($payload)->toBe([
            'jobId' => 'job-bc-3',
            'method' => 'storeCarDocuments',
            'status' => 'failed',
            'message' => 'Timeout',
            'errors' => [['chassis' => 'Z1', 'error' => 'Not found']],
        ]);
    });

    it('does not broadcast when broadcasting is disabled', function () {
        config(['dinas-shipping-sdk.webhook_jobs.broadcasting.enabled' => false]);

        $event = new ShippingJobResolved(
            jobId: 'job-bc-off',
            userId: 1,
            method: 'storeCarPhotos',
            status: 'finished',
            message: null,
        );

        expect($event->broadcastWhen())->toBeFalse();
    });

    it('does not broadcast when userId is null', function () {
        config(['dinas-shipping-sdk.webhook_jobs.broadcasting.enabled' => true]);

        $event = new ShippingJobResolved(
            jobId: 'job-bc-null',
            userId: null,
            method: 'storeCarPhotos',
            status: 'finished',
            message: null,
        );

        expect($event->broadcastWhen())->toBeFalse()
            ->and($event->broadcastOn())->toBe([]);
    });
});

// ---------------------------------------------------------------------------
// Tests: duplicate webhook is ignored (idempotency via claim)
// ---------------------------------------------------------------------------

describe('webhook idempotency', function () {

    it('does not execute callback twice for the same jobId', function () {
        $shipping = createShippingWithMockedPhotosApi(mockPhotosApi('job-dup'));

        $shipping->storeCarPhotos(
            [['chassis' => 'DUP1', 'album' => 'exterior', 'urls' => ['https://img.test/dup.jpg']]],
            onResolve: function (WebhookJobContext $ctx) {
                $count = cache()->get('dup_call_count', 0);
                cache()->put('dup_call_count', $count + 1, 60);
            },
        );

        Event::fake([ShippingJobResolved::class]);

        // Simulate the same webhook twice
        simulateWebhook('job-dup', 'finished');
        simulateWebhook('job-dup', 'finished');

        // Callback should only run once because claim() prevents re-processing
        expect(cache()->get('dup_call_count', 0))->toBe(1);
    });
});
