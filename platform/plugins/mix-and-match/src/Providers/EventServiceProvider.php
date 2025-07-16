<?php

namespace Botble\MixAndMatch\Providers;

use Botble\Base\Events\PluginActivatedEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PluginActivatedEvent::class => [
            'Botble\MixAndMatch\Listeners\PluginActivatedListener',
        ],
    ];
}
