<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Register a view namespace for our theme partials
        View::addNamespace('theme', [
            base_path('platform/themes/farmart'),
            base_path('platform/themes/farmart/partials'),
        ]);
        
        // Register a view namespace for schema partials
        View::addNamespace('schema', [
            base_path('platform/themes/farmart/partials/schema'),
        ]);
    }
}
