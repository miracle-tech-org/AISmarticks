<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\User'         => 'App\Policies\UserPolicy',
        'App\Mailbox'      => 'App\Policies\MailboxPolicy',
        'App\Folder'       => 'App\Policies\FolderPolicy',
        'App\Conversation' => 'App\Policies\ConversationPolicy',
        'App\Thread'       => 'App\Policies\ThreadPolicy',
    ];

    protected $guards = [
        'web' => [
            'driver' =>'session',
            'provider' => 'users',
        ],
    ];

    protected $passwordBrokers = [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
        ],
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('manage_ai_agents', function ($user) {
            // Authorization logic for managing AI agents
        });
    }
}
