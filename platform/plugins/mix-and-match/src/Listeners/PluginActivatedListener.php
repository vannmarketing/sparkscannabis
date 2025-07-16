<?php

namespace Botble\MixAndMatch\Listeners;

use Botble\Base\Events\PluginActivatedEvent;
use Botble\MixAndMatch\Providers\MixAndMatchServiceProvider;

class PluginActivatedListener
{
    public function handle(PluginActivatedEvent $event): void
    {
        if ($event->plugin === 'mix-and-match') {
            (new MixAndMatchServiceProvider(app()))->publishAssets();
        }
    }
}
