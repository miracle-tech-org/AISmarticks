<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\MondayIntegration\Http\Controllers'], function()
{
    // Route::get('/', 'MondayIntegrationController@index');

    Route::get('/', 'DashboardController@index')->name('dashboard');
    
    // Settings
    Route::get('/settings', 'SettingsController@index')->name('settings');
    Route::post('/settings', 'SettingsController@save')->name('settings.save');
    Route::post('/settings/test-connection', 'SettingsController@testConnection')->name('test-connection');

    // Field Mappings
    Route::get('/mappings', 'FieldMappingController@index')->name('mappings');
    Route::post('/mappings', 'FieldMappingController@save')->name('mappings.save');
    Route::get('/mappings/fields', 'FieldMappingController@getFields')->name('mappings.fields');

    // Webhooks
    Route::get('/webhooks', 'WebhookController@index')->name('webhooks');
    Route::post('/webhooks', 'WebhookController@store')->name('webhooks.store');
    Route::post('/webhooks/{webhook}/test', 'WebhookController@test')->name('webhooks.test');
    Route::post('/webhooks/{webhook}/toggle', 'WebhookController@toggle')->name('webhooks.toggle');
    Route::delete('/webhooks/{webhook}', 'WebhookController@destroy')->name('webhooks.destroy');

    // Documentation
    Route::get('/docs', 'DocumentationController@index')->name('docs');
    Route::get('/docs/{page}', 'DocumentationController@show')->name('docs.show');

    // Sync Operations
    Route::post('/sync/now', 'SyncController@syncNow')->name('sync-now');
    Route::get('/sync/status', 'SyncController@status')->name('sync.status');
    Route::post('/sync/retry/{log}', 'SyncController@retry')->name('sync.retry');

    // API Operations
    Route::post('/api/boards', 'ApiController@getBoards')->name('boards.list');
    Route::post('/api/columns', 'ApiController@getColumns')->name('columns.list');

});


// Webhook Endpoints (no auth)
Route::post('/monday/webhook/{webhook}', 'WebhookController@handle')->name('monday.webhook.handle');
