<?php

namespace Dinas\Shipping\Commands;

use Dinas\Shipping\Facades\Shipping;
use Dinas\ShippingSdk\Model\Webhook;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class ShippingWebhookSetup extends Command
{
    /**
     * @var string
     */
    protected $signature = 'webhook:dinas-shipping
     {name=default : Name of the webhook to act upon, you can manage multiple webhooks by name. Use it in different apps}
     {--events= : Comma separated list of events to subscribe to. Can be empty or * for all events}
     {--url= : Your url to receive the webhook, if it differs from your domain or default route}
     {--i|install : Install/setup the webhook with name, events and url}
     {--test : Test the webhook by sending a test payload}
     {--t|toggle : Toggle the webhook active/inactive status}
     {--r|remove : Remove the webhook by name}';

    /**
     * @var string
     */
    protected $description = 'View and setup Dinas Shipping webhooks. You can use -i to create new one, and -r to delete it.
    By default, it will use name "default".
    You can specify events as comma separated values, or * for all events.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $baseUrl = config('dinas-shipping-sdk.base_url');
        $token = config('dinas-shipping-sdk.token');
        $secret = config('dinas-shipping-sdk.webhook.signing_secret');

        if (empty($baseUrl)) {
            $this->error('DINAS_SHIPPING_BASE_URL is not configured in .env file');
            return self::FAILURE;
        }

        if (empty($token)) {
            $this->error('DINAS_SHIPPING_TOKEN is not configured in .env file');
            return self::FAILURE;
        }

        if (empty($secret)) {
            $this->error('DINAS_SHIPPING_SECRET is not configured in .env file');
            return self::FAILURE;
        }

        $name = $this->argument('name');

        if ($this->option('install')) {
            $this->line('Setting up Dinas Shipping webhook...');
            $this->line("Base URL: $baseUrl");

            return $this->storeWebhook($name, $secret);
        }

        if ($this->option('test')) {
            $this->line('Testing Dinas Shipping webhook...');
            return $this->testWebhook($name);
        }

        if ($this->option('toggle')) {
            $this->line('Toggling Dinas Shipping webhook...');
            return $this->toggleWebhook($name);
        }

        if ($this->option('remove')) {
            $this->line('Removing Dinas Shipping webhook...');
            return $this->removeWebhook($name);
        }

        $this->info('Current Dinas Shipping webhooks:');
        $this->showWebhooks();

        return self::SUCCESS;
    }

    protected function storeWebhook(string $name, string $secret): int
    {
        $this->line('Secret: ' . substr($secret, 0, 10) . '...');

        $webhookUrl = $this->option('url');

        if (!$webhookUrl) {
            if (!Route::has('webhooks.dinas-shipping')) {
                $this->error("Webhook route wasn't found. Add Route::dinasShippingWebhooks('dinas-shipping/webhook') to your routes file");
                return self::FAILURE;
            }

            $webhookUrl = route('webhooks.dinas-shipping');
        }

        $events = $this->option('events');
        // Listen to all events if not specified
        $events = ($events === '*' || !$events) ? ['*'] : explode(',', $events);

        try {
            $webhook = Shipping::storeWebhook([
                'name' => $name,
                'url' => $webhookUrl,
                'secret' => $secret,
                'events' => $events,
            ]);
        } catch (Exception $e) {
            $this->error("Webhook '{$name}' is already created");
            return self::FAILURE;
        }

        $this->info("✓ Webhook '{$name}' is added");
        $this->displayWebhookInfo($webhook);

        return self::SUCCESS;
    }

    protected function showWebhooks(): int
    {
        try {
            $hooks = Shipping::getWebhooks();

            if (!count($hooks)) {
                if ($this->confirm('No webhooks found. Would you like to create the first one?')) {
                    $secret = config('dinas-shipping-sdk.webhook.signing_secret');
                    return $this->storeWebhook('default', $secret);
                }

                $this->info('Ok ¯\_(ツ)_/¯');
                return self::SUCCESS;
            }

            foreach ($hooks as $webhook) {
                $this->displayWebhookInfo($webhook);
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error("Error fetching webhooks: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    protected function testWebhook(string $name): int
    {
        try {
            Shipping::testWebhook($name);
        } catch (Exception $e) {
            $this->error("Webhook '{$name}' not found");
            return self::FAILURE;
        }

        $this->info("✓ Test payload sent to webhook '{$name}'");

        return self::SUCCESS;
    }

    protected function toggleWebhook(string $name): int
    {
        try {
            Shipping::toggleWebhook($name);
        } catch (Exception $e) {
            $this->error("Webhook '{$name}' not found");
            return self::FAILURE;
        }

        $hook = Shipping::getWebhook($name);

        $this->info("✓ Webhook '{$name}' is now " . ($hook->getIsActive() ? 'active' : 'inactive'));

        return self::SUCCESS;
    }

    protected function removeWebhook(string $name): int
    {
        try {
            Shipping::deleteWebhook($name);
        } catch (Exception $e) {
            $this->error("Webhook '{$name}' not found");
            return self::FAILURE;
        }

        $this->info("✓ Webhook '{$name}' is removed");

        return self::SUCCESS;
    }

    /**
     * Display webhook information
     */
    protected function displayWebhookInfo(Webhook $webhook): void
    {
        $this->newLine();
        $this->info("Webhook Information:");

        $this->line("Name: " . $webhook->getName());
        $this->line("URL: " . $webhook->getUrl());
        $this->line("Events: " . implode(', ', $webhook->getEvents()));
        $this->line("Status: " . ($webhook->getIsActive() ? 'Active' : 'Inactive'));
        $lastUsed = $webhook->getLastDeliveryAt();
        $this->line("Last used: " . ($lastUsed ? $lastUsed->format('Y-m-d H:i:s') : 'Never'));
    }
}
