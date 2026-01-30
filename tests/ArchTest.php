<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('main class does not use facades')
    ->expect('Dinas\Shipping\Shipping')
    ->not->toUse('Illuminate\Support\Facades');

arch('all classes have strict types')
    ->expect('Dinas\Shipping')
    ->toUseStrictTypes();

arch('service provider extends laravel service provider')
    ->expect('Dinas\Shipping\ShippingServiceProvider')
    ->toExtend('Illuminate\Support\ServiceProvider');

arch('facade extends laravel facade')
    ->expect('Dinas\Shipping\Facades\Shipping')
    ->toExtend('Illuminate\Support\Facades\Facade');

arch('exceptions extend base exception')
    ->expect('Dinas\Shipping\Exceptions')
    ->toExtend('Exception');

arch('jobs implement ShouldQueue')
    ->expect('Dinas\Shipping\Jobs')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

arch('commands extend laravel command')
    ->expect('Dinas\Shipping\Commands')
    ->toExtend('Illuminate\Console\Command');

arch('main shipping class has proper dependencies')
    ->expect('Dinas\Shipping\Shipping')
    ->toOnlyUse([
        'Dinas\ShippingSdk',
        'GuzzleHttp',
        'Psr\Http\Client',
        'Illuminate\Support', // for config() helper
        'config', // Laravel helper function
    ]);

arch('namespace follows PSR-4')
    ->expect('Dinas\Shipping')
    ->toBeClasses()
    ->ignoring('Dinas\Shipping\Tests');
