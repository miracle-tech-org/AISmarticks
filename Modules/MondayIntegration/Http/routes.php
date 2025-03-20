<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\MondayIntegration\Http\Controllers'], function() {
    Route::prefix('monday')->name('monday.')->group(function() {
        Route::get('/mailbox/{mailbox_id}/monday-dashboard', 'DashboardController@index')->name('dashboard');
        Route::post('/test-connection', 'SettingsController@testConnection')->name('test-connection');
        Route::post('/test-board-connection', 'MondaySettingsController@testBoardConnection')->name('test-board-connection');
        Route::post('/sync-now', 'DashboardController@syncNow')->name('sync-now');

        // Settings
        Route::get('/mailbox/{mailbox_id}/monday-settings', 'MondaySettingsController@index')->name('settings');
        Route::post('/mailbox/{mailbox_id}/monday-settings', 'MondaySettingsController@save')->name('settings.save');

        // Pullboard Settings
        Route::get('/mailbox/{mailbox_id}/pullboard', 'MondaySettingsController@pullboardSettings')->name('settings.pullboard');
        
        // Pushboard Settings
        Route::get('/mailbox/{mailbox_id}/pushboard', 'MondaySettingsController@pushboardSettings')->name('settings.pushboard');

        // Field Mapping
        Route::get('/mailbox/{mailbox_id}/field-mapping', 'FieldMappingController@index')->name('mappings');
        Route::post('/field-mapping/save', 'FieldMappingController@save')->name('mappings.save');
        Route::post('/field-mapping/validate', 'FieldMappingController@validate')->name('mappings.validate');
        Route::post('/field-mapping/import', 'FieldMappingController@import')->name('mappings.import');
        Route::get('/field-mapping/export', 'FieldMappingController@export')->name('mappings.export');
        Route::get('/field-mapping/fields', 'FieldMappingController@getFields')->name('mappings.fields');
        Route::get('/field-mapping/columns/{boardId}', 'FieldMappingController@getBoardColumns')->name('mappings.board.columns');
        Route::post('/settings/field-mapping/suggest', 'FieldMappingController@suggestMappings')->name('mappings.suggest');
        Route::post('/settings/field-mapping/validate-compatibility', 'FieldMappingController@validateCompatibility')->name('mappings.validate-compatibility');
    });
});