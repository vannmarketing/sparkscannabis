<?php

namespace Botble\Ecommerce\Providers;

use Botble\Ecommerce\Commands\CancelExpiredDeletionRequests;
use Botble\Ecommerce\Commands\FixWordPressPasswordsCommand;
use Botble\Ecommerce\Commands\ImportWordPressCustomersCommand;
use Botble\Ecommerce\Commands\SendAbandonedCartsEmailCommand;
use Botble\Ecommerce\Commands\UpdateCustomerAddressesCommand;
use Botble\Ecommerce\Commands\UpdateCustomerNamesCommand;
use Botble\Ecommerce\Commands\UpdateVariationImagesCommand;
use Botble\Ecommerce\Models\SharedWishlist;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            SendAbandonedCartsEmailCommand::class,
            CancelExpiredDeletionRequests::class,
            UpdateVariationImagesCommand::class,
            ImportWordPressCustomersCommand::class,
            FixWordPressPasswordsCommand::class,
            UpdateCustomerNamesCommand::class,
            UpdateCustomerAddressesCommand::class,
        ]);

        $this->app->afterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(SendAbandonedCartsEmailCommand::class)->weekly();
            $schedule->command(CancelExpiredDeletionRequests::class)->daily();
            $schedule->command('model:prune', [
                '--model' => [SharedWishlist::class],
            ])->daily();
        });
    }
}
