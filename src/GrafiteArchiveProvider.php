<?php

namespace Grafite\Archive;

use Grafite\Archive\Commands\ArchiveRunCommand;
use Grafite\Archive\Services\ArchiveManager;
use Illuminate\Support\ServiceProvider;

class GrafiteArchiveProvider extends ServiceProvider
{
    /**
     * Boot method.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/archive.php' => base_path('config/archive.php'),
        ]);

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../views', 'archive');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/archive.php', 'archive');

        $this->app->singleton(ArchiveManager::class, function ($app) {
            return new ArchiveManager($app['config'], $app['files']);
        });

        $this->commands([
            ArchiveRunCommand::class,
        ]);
    }
}
