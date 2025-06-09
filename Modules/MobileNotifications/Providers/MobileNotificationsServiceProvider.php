<?php

namespace Modules\MobileNotifications\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event; // Added import for Event Facade
use App\Events\CustomerReplied;
use App\Events\ConversationUserChanged;
use Modules\MobileNotifications\Listeners\NotifyAgentOnCustomerReply;
use Modules\MobileNotifications\Listeners\NotifyAgentOnAssignment;
use Illuminate\Cache\RateLimiting\Limit; // Added for Rate Limiter
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use App\Option; // Added for Option model
use Illuminate\Support\Facades\Schema; // To check if 'options' table exists

// Module alias
if (!defined('MN_MODULE')) {
    define('MN_MODULE', 'mobilenotifications');
}

class MobileNotificationsServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        CustomerReplied::class => [
            NotifyAgentOnCustomerReply::class,
        ],
        ConversationUserChanged::class => [
            NotifyAgentOnAssignment::class,
        ],
    ];

    public function boot()
    {
        // Register config first so its defaults are available.
        $this->registerConfig();

        // $this->loadSettingsFromOptions(); // Temporarily commented out to allow migrations if it causes Auth issues
        \Illuminate\Support\Facades\Log::info('MobileNotificationsServiceProvider: loadSettingsFromOptions temporarily disabled for migration.');


        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        // $this->registerListeners(); // Temporarily commented out
        \Illuminate\Support\Facades\Log::info('MobileNotificationsServiceProvider: registerListeners temporarily disabled for migration.');
        // $this->configureRateLimiting(); // Commented out due to RateLimiter facade not available in L5.5
        $this->hooks();
    }

    protected function loadSettingsFromOptions()
    {
        // Ensure Option class exists and options table is present (e.g. during initial setup)
        if (!class_exists(Option::class) || !Schema::hasTable('options')) {
            return;
        }

        $settingsKeys = [
            'project_id',
            'server_name',
            'default_title',
            'enabled',
            'firebase_credentials_path', // This was previously 'firebase_credentials' in the old table
        ];

        foreach ($settingsKeys as $key) {
            $optionKey = 'mobilenotifications_' . $key;
            $configKey = 'mobilenotifications.' . $key;

            // Get default from the already merged Config/config.php file
            $defaultValue = config($configKey);

            // Fetch from options table, using file default if not found in options
            $valueFromOptions = Option::get($optionKey, $defaultValue);

            // Special handling for 'enabled' to ensure it's boolean
            if ($key === 'enabled') {
                // If the value from options is a string '0' or '1', convert to boolean
                if ($valueFromOptions === '0') {
                    $valueFromOptions = false;
                } elseif ($valueFromOptions === '1') {
                    $valueFromOptions = true;
                }
                // Otherwise, filter_var should handle it, including boolean defaults
                $valueFromOptions = filter_var($valueFromOptions, FILTER_VALIDATE_BOOLEAN, ['flags' => FILTER_NULL_ON_FAILURE]);
                if ($valueFromOptions === null) {
                    $valueFromOptions = filter_var($defaultValue, FILTER_VALIDATE_BOOLEAN);
                }
            }

            // Special handling for firebase_credentials_path
            if ($key === 'firebase_credentials_path') {
                if (!empty($valueFromOptions)) {
                    // If valueFromOptions is the same as the default, it means it likely came from the config file
                    // (which already uses storage_path()) or it was never set in options table.
                    // We only prepend storage_path if it's a relative path coming from the options table.
                    $isLikelyDefaultAbsPath = ($valueFromOptions === $defaultValue && preg_match('/^([A-Za-z]:\\\|\\|\/)/', $valueFromOptions));

                    if (!$isLikelyDefaultAbsPath && !preg_match('/^([A-Za-z]:\\\|\\|\/)/', $valueFromOptions)) {
                         // It's a relative path (e.g., "mobilenotifications/file.json") and not the default absolute path
                         $valueFromOptions = storage_path('app/' . $valueFromOptions);
                    }
                } elseif (empty($valueFromOptions) && !empty($defaultValue)) {
                    // If value from options is empty, but default is not, ensure default (which should be absolute) is used.
                     $valueFromOptions = $defaultValue;
                }
            }

            // Set the config value for the application's current lifecycle
            if ($valueFromOptions !== null) { // Check not null in case Option::get returns null and no default.
                config()->set($configKey, $valueFromOptions);
            }
        }
    }

    public function hooks() {
        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('mobilenotifications::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 35);

        \Eventy::addFilter('menu.selected', function($menu) {
            $menu['mobilenotifications.settings'] = [
                'mobilenotifications.settings',
            ];
            $menu['mobilenotifications.devices.index'] = [
                'mobilenotifications.devices.index',
            ];
            $menu['mobilenotifications.logs.index'] = [
                'mobilenotifications.logs.index',
            ];
            return $menu;
        });
    }

    protected function configureRateLimiting()
    {
        /* // RateLimiter Facade not available in L5.5 - entire method content commented out.
        RateLimiter::for('mobilenotifications_register_device', function (Request $request) {
            // Limit to 10 registration attempts per IP address per minute.
            return Limit::perMinute(10)->by($request->ip());
        });
        */
    }

    public function register()
    {
    }

    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('mobilenotifications.php'), // Main settings
            // Note: notification_types.php is not typically published as it's module-internal logic.
        ], 'config');

        // Merge main module settings
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'mobilenotifications'
        );

        // Merge notification types settings
        $this->mergeConfigFrom(
            __DIR__.'/../Config/notification_types.php', 'mobilenotifications.notification_types'
        );
    }

    protected function registerViews()
    {
        $viewPath = resource_path('views/modules/mobilenotifications');
        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom([$sourcePath, $viewPath], 'mobilenotifications');
    }

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    protected function registerListeners()
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }
}
