<?php

namespace Dinas\Shipping\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Dinas\Shipping\Shipping
 */
class Shipping extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Dinas\Shipping\Shipping::class;
    }
}
