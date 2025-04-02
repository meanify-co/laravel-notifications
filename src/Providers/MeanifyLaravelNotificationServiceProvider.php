<?php

namespace Meanify\LaravelNotifications\Providers;

use Illuminate\Support\ServiceProvider;
use Meanify\LaravelNotifications\Console\Commands\TestNotificationCommand;

class MeanifyLaravelNotificationServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot()
    {
        //Config
        $this->publishes([
            __DIR__.'/../Config/meanify-laravel-notifications.php' => config_path('meanify-laravel-notifications.php'),
        ], 'meanify-configs');

        //Models
        $this->publishes([
            __DIR__.'/../Models' => app_path('Models'),
        ], 'meanify-models');


        //Seeders
        $this->publishes([
            __DIR__.'/../Database/Seeders' => database_path('seeders'),
        ], 'meanify-seeders');


        //Migrations
        $this->publishes([
            __DIR__.'/../Database/migrations' => database_path('migrations'),
        ], 'meanify-migrations');
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->commands([
            TestNotificationCommand::class,
        ]);
    }
}
