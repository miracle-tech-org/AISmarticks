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
        Route::post('/get-monday-items', 'MondaySettingsController@getBoardItems')->name('get.board-items');
        Route::post('/delete-board-item', 'MondaySettingsController@deleteBoardItem')->name('delete.board-item');
        Route::post('/create-board-item', 'MondaySettingsController@createBoardItem')->name('create.board-item');
        Route::get('/clear-mapping', 'MondaySettingsController@clearMapping')->name('settings.clear');

        // Pullboard Settings
        Route::get('/mailbox/{mailbox_id}/pullboard', 'MondaySettingsController@pullboardSettings')->name('settings.pullboard');
        
        // Pushboard Settings
        Route::get('/mailbox/{mailbox_id}/pushboard', 'MondaySettingsController@pushboardSettings')->name('settings.pushboard');
        Route::post('/pullboard/create',              'MondaySettingsController@createBoard')->name('board.save');

        // Queue Settings
        Route::get('/mailbox/{mailbox_id}/queue', 'QueueSettingsController@index')->name('queue_settings');
        Route::post('/queue/save', 'QueueSettingsController@save')->name('queue_settings.save');
        
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
        
        // Sidebar routes
        Route::get('/mailbox/{mailbox_id}/sidebar', 'SidebarWidgetController@index')->name('sidebar_settings');
        Route::get('/sidebarWidget/{conversationId}', 'SidebarController@sidebarWidget')->name('sidebar_widget');
        
        Route::get('/sidebar/search', 'SidebarController@search')->name('sidebar.search');
        Route::post('/sidebar/create-mapping', 'SidebarController@createMapping')->name('sidebar.create-mapping');
        Route::get('/sidebar/get-item/{itemId}', 'SidebarController@getItem')->name('sidebar.get-item');
        Route::post('/sidebar/add-update', 'SidebarController@addUpdate')->name('sidebar.add-update');
        Route::post('/sidebar/change-status', 'SidebarController@changeStatus')->name('sidebar.change-status');
        Route::get('/sidebar/conversation/{conversation_id}', 'SidebarController@getConversationData')->name('sidebar.conversation');
        Route::post('/sidebar/search', 'SidebarController@searchCustomer')->name('sidebar.search');
        Route::post('/sidebar/create-item', 'SidebarController@createItem')->name('sidebar.create_item');
        Route::post('/sidebar/execute-action', 'SidebarController@executeAction')->name('sidebar.execute_action');
        Route::post('/sidebar/search-for-linking', 'SidebarController@searchForLinking')->name('sidebar.search_for_linking');
        Route::post('/sidebar/create-manual-link', 'SidebarController@createManualLink')->name('sidebar.create_manual_link');
    });
});