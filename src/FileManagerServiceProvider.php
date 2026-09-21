<?php

namespace TeknikHub\FileManager;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class FileManagerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/teknikhub-file-manager.php', 'teknikhub-file-manager'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->registerPublishing();
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    /**
     * Get the File Manager route group configuration array.
     */
    protected function routeConfiguration(): array
    {
        return [
            'prefix' => config('teknikhub-file-manager.route_prefix', 'admin/file-manager'),
            'middleware' => config('teknikhub-file-manager.middleware', ['web', 'auth']),
            'as' => 'teknikhub.file-manager.',
        ];
    }

    /**
     * Register the package views.
     */
    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'teknikhub-file-manager');

        // Note: For Laravel 10/11+ Blade components loading from packages
        $this->loadViewComponentsAs('teknikhub', [
            \TeknikHub\FileManager\View\Components\FilePicker::class,
            \TeknikHub\FileManager\View\Components\FileManager::class,
        ]);
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Config
            $this->publishes([
                __DIR__ . '/../config/teknikhub-file-manager.php' => config_path('teknikhub-file-manager.php'),
            ], 'teknikhub-file-manager-config');

            // Assets
            $this->publishes([
                __DIR__ . '/../resources/css' => public_path('vendor/teknikhub/file-manager/css'),
                __DIR__ . '/../resources/js' => public_path('vendor/teknikhub/file-manager/js'),
            ], 'teknikhub-file-manager-assets');

            // Views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/teknikhub-file-manager'),
            ], 'teknikhub-file-manager-views');
        }
    }
}
