<?php

namespace Botble\FreeGifts\Providers;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\DashboardMenu;
use Botble\Base\Facades\PanelSectionManager;
use Botble\Base\PanelSections\PanelSectionItem;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Ecommerce\PanelSections\EcommercePanelSection;
use Botble\FreeGifts\Http\Middleware\ProcessFreeGiftsMiddleware;
use Botble\FreeGifts\Models\Setting;
use Botble\FreeGifts\Services\FreeGiftsService;
use Botble\FreeGifts\Services\FreeGiftsSettingService;
use Botble\FreeGifts\Services\ManualGiftService;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\ServiceProvider;

class FreeGiftsServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->app->singleton(FreeGiftsSettingService::class);
        $this->app->singleton(FreeGiftsService::class);
        $this->app->singleton(ManualGiftService::class);

        $this->app->register(EventServiceProvider::class);

        $this->setNamespace('plugins/free-gifts')
            ->loadHelpers()
            ->loadAndPublishConfigurations(['permissions'])
            ->loadMigrations()
            ->loadAndPublishTranslations()
            ->loadAndPublishViews()
            ->loadRoutes();
    }

    public function boot(): void
    {
        // Load Constants
        require_once __DIR__ . '/Constants.php';

        // Register middleware
        $this->app['router']->aliasMiddleware('process-free-gifts', ProcessFreeGiftsMiddleware::class);
        $this->app['router']->pushMiddlewareToGroup('web', ProcessFreeGiftsMiddleware::class);

        // Publish assets
        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/core/plugins/free-gifts'),
        ], 'public');

        $this->app['events']->listen(RouteMatched::class, function () {
            DashboardMenu::make()
                ->registerItem([
                    'id' => 'cms-plugins-free-gifts',
                    'priority' => 5,
                    'parent_id' => 'cms-plugins-ecommerce',
                    'name' => 'plugins/free-gifts::free-gifts.name',
                    'icon' => 'fa fa-gift',
                    'url' => route('free-gifts.index'),
                    'permissions' => ['free-gifts.index'],
                ]);

            // Register submenu items
            DashboardMenu::make()
                ->registerItem([
                    'id' => 'cms-plugins-free-gifts-gift-rules',
                    'priority' => 1,
                    'parent_id' => 'cms-plugins-free-gifts',
                    'name' => 'plugins/free-gifts::gift-rules.name',
                    'icon' => 'fa fa-list',
                    'url' => route('gift-rules.index'),
                    'permissions' => ['gift-rules.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-free-gifts-manual-gifts',
                    'priority' => 2,
                    'parent_id' => 'cms-plugins-free-gifts',
                    'name' => 'plugins/free-gifts::manual-gifts.name',
                    'icon' => 'fa fa-gift',
                    'url' => route('manual-gifts.index'),
                    'permissions' => ['manual-gifts.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-free-gifts-settings',
                    'priority' => 3,
                    'parent_id' => 'cms-plugins-free-gifts',
                    'name' => 'plugins/free-gifts::settings.name',
                    'icon' => 'fa fa-cog',
                    'url' => route('free-gifts.settings.index'),
                    'permissions' => ['free-gifts.settings'],
                ]);

            PanelSectionManager::registerItem(
                EcommercePanelSection::class,
                fn () => PanelSectionItem::make('free-gifts')
                    ->setTitle(trans('plugins/free-gifts::free-gifts.name'))
                    ->withIcon('ti ti-gift')
                    ->withDescription(trans('plugins/free-gifts::free-gifts.description'))
                    ->withPriority(500)
                    ->withRoute('free-gifts.index')
            );

            // Add admin assets
            if (is_in_admin()) {
                Assets::addStylesDirectly([
                    'vendor/core/plugins/free-gifts/css/free-gifts-admin.css',
                ]);
            }
            
            // Add assets to frontend
            if (function_exists('Theme')) {
                Assets::addStylesDirectly([
                    'vendor/core/plugins/free-gifts/css/free-gifts.css',
                ])
                ->addScriptsDirectly([
                    'vendor/core/plugins/free-gifts/js/free-gifts.js',
                ]);

                // Add settings to JS global variable
                $settings = app(FreeGiftsSettingService::class)->getSettings();
                add_filter(THEME_FRONT_FOOTER, function ($html) use ($settings) {
                    return $html . view('plugins/free-gifts::partials.footer-script', compact('settings'))->render();
                }, 15);
            }
        });
    }
}
