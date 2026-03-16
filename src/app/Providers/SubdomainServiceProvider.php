<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class SubdomainServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/subdomains.php',
            'subdomains'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrations();
        $this->loadRoutes();
        $this->loadViews();
        $this->loadTranslations();
        $this->registerPublishing();
        $this->registerMiddleware();
        $this->registerObservers();
    }

    /**
     * Load database migrations.
     */
    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    /**
     * Load plugin routes.
     */
    protected function loadRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/subdomains.php');
    }

    /**
     * Load Blade views.
     */
    protected function loadViews(): void
    {
        $this->loadViewsFrom(
            __DIR__ . '/../../resources/views',
            'subdomains'
        );
    }

    /**
     * Load translation files.
     */
    protected function loadTranslations(): void
    {
        $this->loadTranslationsFrom(
            __DIR__ . '/../../resources/lang',
            'subdomains'
        );
    }

    /**
     * Register publishable assets.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Config
            $this->publishes([
                __DIR__ . '/../../config/subdomains.php' => config_path('subdomains.php'),
            ], 'subdomains-config');

            // Views
            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/subdomains'),
            ], 'subdomains-views');

            // Migrations
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'subdomains-migrations');

            // Translations
            $this->publishes([
                __DIR__ . '/../../resources/lang' => lang_path('vendor/subdomains'),
            ], 'subdomains-lang');
        }
    }

    /**
     * Register middleware aliases.
     */
    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('subdomain.ratelimit', \App\Http\Middleware\SubdomainRateLimit::class);
    }

    /**
     * Register model observers for server lifecycle integration.
     */
    protected function registerObservers(): void
    {
        // Register the ServerObserver if the PteroCA Server model exists.
        // This handles auto-suspend/delete of DNS records on server state changes.
        if (class_exists(\App\Models\Server::class)) {
            \App\Models\Server::observe(\App\Observers\ServerObserver::class);
        }
    }
}
