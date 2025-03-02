<?php

namespace Modules\MondayIntegration\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class MondayIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        \Eventy::addAction('menu.manage.after_mailboxes', function($mailbox) {
        }, 15);

        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('mondayintegration::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 35);

        \Eventy::addFilter('menu.selected', function($menu) {
            $menu['monday.settings'] = [
                'monday.settings',
            ];
            return $menu;
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();

        // Register services
        $this->app->singleton(MondayApiService::class, function ($app) {
            return new MondayApiService(
                $app->make(SecurityService::class)
            );
        });

        $this->app->singleton(SecurityService::class, function ($app) {
            return new SecurityService();
        });

        $this->app->singleton(WebhookService::class, function ($app) {
            return new WebhookService(
                $app->make(SecurityService::class)
            );
        });

        $this->app->singleton(DataTransformer::class, function ($app) {
            return new DataTransformer();
        });
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('mondayintegration.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'mondayintegration'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/mondayintegration');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/mondayintegration';
        }, \Config::get('view.paths')), [$sourcePath]), 'mondayintegration');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    protected function registerSidebarItem()
    {
        \App\Sidebar\Sidebar::register(function($sidebar) {
            $sidebar->addItem(
                'monday',
                'Monday.com',
                route('monday.dashboard'),
                'glyphicon glyphicon-calendar',
                10
            );
        });
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
