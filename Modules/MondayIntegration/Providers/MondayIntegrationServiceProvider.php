<?php

namespace Modules\MondayIntegration\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\MondayIntegration\Http\Controllers\SidebarController;
use App\Conversation;
use App\Thread;
use GuzzleHttp\Client;

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

    public function pushMonday($conversation) {
        if(\Module::isActive('mondayintegration')) {
            $originalStatus = $conversation->getOriginal('status');
            $newStatus = $conversation->status;

            $settings = \Modules\MondayIntegration\Models\MondaySettings::all()->pluck('value', 'key')->toArray();
            $push_on_conversation_closed = !empty($settings['push_on_conversation_closed']);
            $push_on_conversation_fully_closed = !empty($settings['push_on_conversation_fully_closed']);
            $push_on_conversation_created = !empty($settings['push_on_conversation_created']);
            $push_on_conversation_updated = !empty($settings['push_on_conversation_updated']);

            try {
                if ($originalStatus !== $newStatus && $newStatus == 3) {
                    if($push_on_conversation_closed || $push_on_conversation_fully_closed) {
                        $thread = Thread::where('conversation_id', $conversation->id)->orderByDesc('id')->first();
                        $requestData = [];
                        if($push_on_conversation_fully_closed) {
                            $requestData = [
                                'conversation_id' => $thread->conversation_id,
                                'thread_id' => $thread->id,
                                'upload' => 'yes',
                            ];
                        } else {
                            $requestData = [
                                'conversation_id' => $thread->conversation_id,
                                'thread_id' => $thread->id,
                            ];
                        }

                        $client = new Client();
                        $client->request('POST', route('monday.send.pushboard.item'), [
                            'http_errors' => false,
                            'json' => $requestData
                        ]);
                        return;
                    }
                }

                if($newStatus != 3 && $newStatus != 1 && $push_on_conversation_updated) {
                    $thread = Thread::where('conversation_id', $conversation->id)->orderByDesc('id')->first();
                    $requestData = [
                        'conversation_id' => $thread->conversation_id,
                        'thread_id' => $thread->id,
                    ];

                    $client = new Client();
                    $client->request('POST', route('monday.send.pushboard.item'), [
                        'http_errors' => false,
                        'json' => $requestData
                    ]);
                    return;
                }
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        \Eventy::addAction('tag.attached', function($tag, $conversation_id) {
            $conversation = Conversation::find($conversation_id);
            $this->pushMonday($conversation);
        }, 20, 2);
        
        \Eventy::addAction('tag.detached', function($tag, $conversation_id) {
            $conversation = Conversation::find($conversation_id);
            $this->pushMonday($conversation);
        }, 20, 2);

        \Eventy::addAction('conversation.note_added', function($conversation, $thread) {
            $this->pushMonday($conversation);
        }, 20, 2);

        // \Eventy::addAction('conversation.send_reply_save', function($conversation, $thread) {
        //     $sidebarController = app(SidebarController::class);
        //     $mondayItems = $sidebarController->sidebarWidget($conversation->id);
        // }, 20, 3);

        // Register assets
        // \Eventy::addFilter('javascripts', function($javascripts) {
            // $javascripts[] = public_path('modules/mondayintegration/js/sidebar-widget.js');
            // $javascripts[] = public_path('modules/mondayintegration/js/templates-index.js');
            // $javascripts[] = public_path('modules/mondayintegration/js/templates-editor.js');
            // $javascripts[] = public_path('modules/mondayintegration/js/field-mapping.js');
            // return $javascripts;
        // });

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

        $this->registerSidebarItem();
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
        // \App\Sidebar\Sidebar::register(function($sidebar) {
        //     $sidebar->addItem(
        //         'monday',
        //         'Monday.com',
        //         route('monday.dashboard'),
        //         'glyphicon glyphicon-calendar',
        //         10
        //     );
        // });
    }

    protected function registerConversationSidebarWidget()
    {
        // Add widget to conversation sidebar
        \Eventy::addAction('conversation.after_sidebar_tabs_content', function($conversation) {
            if ($conversation && $conversation->id) {
                echo \App::make('Modules\MondayIntegration\Http\Controllers\SidebarController')
                    ->sidebarWidget($conversation->id);
            }
        }, 20);
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
