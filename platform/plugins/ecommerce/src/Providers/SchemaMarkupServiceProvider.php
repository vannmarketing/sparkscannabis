<?php

namespace Botble\Ecommerce\Providers;

use Botble\Base\Traits\LoadAndPublishDataTrait;
use Illuminate\Support\ServiceProvider;
use Botble\Ecommerce\Facades\EcommerceHelper;

class SchemaMarkupServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot()
    {
        $this->setNamespace('plugins/ecommerce')
            ->loadAndPublishViews('schemas');

        // Register view composers if needed
        $this->app->booted(function () {
            // Add any view composers here if needed
        });
    }
}
