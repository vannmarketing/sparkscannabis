<?php

namespace Botble\MixAndMatch\Providers;

use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Base\Facades\Assets;
use Botble\Ecommerce\Models\Product;
use Botble\MixAndMatch\Models\MixAndMatchProduct;
use Botble\MixAndMatch\Models\MixAndMatchSetting;
use Botble\MixAndMatch\Listeners\SaveMixAndMatchListener;
use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Route;
use Botble\MixAndMatch\Providers\EventServiceProvider;

class MixAndMatchServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
    }

    public function boot(): void
    {
        $this
            ->setNamespace('plugins/mix-and-match')
            ->loadAndPublishConfigurations(['permissions'])
            ->loadMigrations()
            ->loadAndPublishTranslations()
            ->loadAndPublishViews()
            ->loadRoutes();
            
        // Publish override views
        if (is_dir($this->getPath('resources/views/override'))) {
            $this->publishes([
                $this->getPath('resources/views/override') => base_path('platform/themes'),
            ], 'cms-theme-overrides');
        }

        $this->app->booted(function () {
            $this->app->register(EventServiceProvider::class);
        });

        Assets::addStylesDirectly('vendor/core/plugins/mix-and-match/css/frontend.css');

        add_action(ECOMMERCE_PRODUCT_DETAIL_EXTRA_HTML, function ($product) {
            if ($product->isMixAndMatch()) {
                echo view('plugins/mix-and-match::frontend.product-detail-mix-and-match', compact('product'))->render();
            }
        }, 99);

        // Add a view composer for the cart template to display mix and match items
        view()->composer(['plugins/ecommerce::orders.checkout.cart', 'themes.*.views.ecommerce.cart', 'themes.*.views.ecommerce.checkout'], function ($view) {
            $view->with('mixAndMatchView', 'plugins/mix-and-match::cart.mix-and-match-items');
        });
        
        // Add hook to display mix and match items in cart
        add_filter('ecommerce_cart_item_options_extras', function ($html, $cartItem) {
            if (!empty($cartItem->options['is_mix_and_match'])) {
                $html .= view('plugins/mix-and-match::cart.mix-and-match-items', [
                    'options' => $cartItem->options,
                ])->render();
            }
            
            return $html;
        }, 120, 2);
        
        // Add hook to display mix and match items in checkout page
        add_filter('ecommerce_checkout_detail_item_info', function ($html, $cartItem) {
            if (!empty($cartItem->options['is_mix_and_match'])) {
                $html .= view('plugins/mix-and-match::cart.mix-and-match-items', [
                    'options' => $cartItem->options,
                    'checkout' => true,
                ])->render();
            }
            
            return $html;
        }, 120, 2);
        
        // Add hook to display mix and match items in order detail
        add_filter('ecommerce_order_detail_item_info', function ($html, $orderProduct) {
            $options = $orderProduct->options ? json_decode($orderProduct->options, true) : [];
            if (!empty($options['is_mix_and_match'])) {
                $html .= view('plugins/mix-and-match::cart.mix-and-match-items', [
                    'options' => $options,
                    'order' => true,
                ])->render();
            }
            
            return $html;
        }, 120, 2);

        // Add relationship to Product model
        Product::resolveRelationUsing('mixAndMatchItems', function (Product $product) {
            return $product->hasMany(MixAndMatchProduct::class, 'container_product_id');
        });

        // Register event listeners for CreatedContentEvent
        $this->app['events']->listen(CreatedContentEvent::class, SaveMixAndMatchListener::class);

        // Register event listeners for UpdatedContentEvent
        $this->app['events']->listen(UpdatedContentEvent::class, SaveMixAndMatchListener::class);

        // Register event listeners for RouteMatched
        $this->app['events']->listen(RouteMatched::class, function () {
            // Add Mix and Match section to product form
            add_filter('ecommerce_product_variation_form_end', function ($html) {
                $product = request()->route('product');
                return $html . view('plugins/mix-and-match::partials.mix-and-match-section', ['product' => $product])->render();
            }, 120, 1);

            // Add Mix and Match section to product detail page in the cart form
            add_filter(ECOMMERCE_PRODUCT_DETAIL_EXTRA_HTML, function ($html, $product) {
                if ($product->isMixAndMatch() && $product->mixAndMatchItems->isNotEmpty()) {
                    // Load frontend CSS for mix and match
                    Assets::addStylesDirectly('vendor/core/plugins/mix-and-match/css/frontend.css');
                    return $html . view('plugins/mix-and-match::frontend.product-detail-mix-and-match', ['product' => $product])->render();
                }
                return $html;
            }, 120, 2);

            // Add JavaScript and CSS for Mix and Match functionality
            if (in_array(Route::currentRouteName(), ['products.create', 'products.edit'])) {
                Assets::addStylesDirectly('vendor/core/plugins/mix-and-match/css/mix-and-match.css')
                    ->addScriptsDirectly('vendor/core/plugins/mix-and-match/js/mix-and-match.js');
            }
        });
    }
}
