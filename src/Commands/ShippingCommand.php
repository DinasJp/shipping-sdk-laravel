<?php

namespace Dinas\Shipping\Commands;

use Illuminate\Console\Command;

class ShippingCommand extends Command
{
    public $signature = 'shipping-sdk-laravel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
