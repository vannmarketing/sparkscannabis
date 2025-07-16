<?php

namespace Botble\FreeGifts\Providers;

use Botble\Ecommerce\Events\OrderPlacedEvent;
use Botble\FreeGifts\Listeners\OrderPlacedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPlacedEvent::class => [
            OrderPlacedListener::class,
        ],
    ];
}
