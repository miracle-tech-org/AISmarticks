<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\MondayIntegration\Http\Controllers'], function()
{
    // Route::get('/', 'MondayIntegrationController@index');

    Route::get('/mailbox/{mailbox_id}/monday-dashboard', 'DashboardController@index')->name('monday.dashboard');

    // Sync Operations
    Route::post('/sync/now', 'DashboardController@syncNow')->name('monday.sync-now');
    Route::get('/sync/status', 'DashboardController@status')->name('monday.sync.status');
    Route::post('/sync/retry/{log}', 'DashboardController@retry')->name('monday-sync.retry');

    // Settings
    Route::get('/mailbox/{mailbox_id}/monday-settings', 'SettingsController@index')->name('monday.settings');
    Route::post('/monday-settings', 'SettingsController@save')->name('monday.settings.save');
    Route::post('/monday/test-connection', 'SettingsController@testConnection')->name('monday.test-connection');

    // Field Mappings
    Route::get('/mailbox/{mailbox_id}/mappings', 'FieldMappingController@index')->name('monday.mappings');
    Route::post('/mappings', 'FieldMappingController@save')->name('monday.mappings.save');
    Route::get('/mappings/fields', 'FieldMappingController@getFields')->name('monday.mappings.fields');

    // Sidebar Widget
    Route::get('/mailbox/{mailbox_id}/monday-sidebar/{conversation}', 'SidebarController@sidebarWidget')->name('sidebar-widget');
    Route::post('/quick-action', 'SidebarController@executeQuickAction')->name('quick-action');

    // Webhooks
    Route::get('/webhooks', 'WebhookController@index')->name('webhooks');
    Route::post('/webhooks', 'WebhookController@store')->name('webhooks.store');
    Route::post('/webhooks/{webhook}/test', 'WebhookController@test')->name('webhooks.test');
    Route::post('/webhooks/{webhook}/toggle', 'WebhookController@toggle')->name('webhooks.toggle');
    Route::delete('/webhooks/{webhook}', 'WebhookController@destroy')->name('webhooks.destroy');

    // Documentation
    Route::get('/docs', 'DocumentationController@index')->name('docs');
    Route::get('/docs/{page}', 'DocumentationController@show')->name('docs.show');

    // API Operations
    Route::post('/api/boards', 'SettingsController@getBoards')->name('monday.boards.list');
    Route::post('/api/columns', 'ApiController@getColumns')->name('columns.list');
});


// Webhook Endpoints (no auth)
Route::post('/monday/webhook/{webhook}', 'WebhookController@handle')->name('monday.webhook.handle');
