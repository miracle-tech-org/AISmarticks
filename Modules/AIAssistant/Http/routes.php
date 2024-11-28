<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\AIAssistant\Http\Controllers'], function()
{
    Route::get('/ai-agents/session', 'AIAssistantController@index')->name("aiagents.session");

    Route::get('/ai-assistant/limits',                      'AIAssistantController@showLimits')->name('ai-assistant.limits');
    Route::post('/ai-assistant/limits',                     'AIAssistantController@saveLimits')->name('ai-assistant.save-limits');
 
    Route::get("/ai-agents/{mailbox_id}",                   'AIAgentController@index')->name("aiagents.index");
    Route::get("/ai-agents/{mailbox_id}/create",            'AIAgentController@create')->name("aiagents.create");
    Route::post("/ai-agents/{mailbox_id}/store",            'AIAgentController@store')->name("aiagents.store");
    Route::get("/ai-agents/{mailbox_id}/edit",              'AIAgentController@edit')->name("aiagents.edit");
    Route::post("/ai-agents/{mailbox_id}/update/{id}",      'AIAgentController@update')->name("aiagents.update");
    Route::post("/ai-agents/{mailbox_id}/destory/{id}",     'AIAgentController@destroy')->name("aiagents.destroy");

    Route::get('/ai-reports',                               "ReportingController@index")->name("reports.agent");
    Route::get('/ai-reports/{id}',                          "ReportingController@show")->name("reports.show");

    Route::get('/ai-assignments/{id}',                      "AIAssignmentController@index")->name("assignments.index");
    Route::post('/ai-assignments/{id}/store',               "AIAssignmentController@store")->name("assignments.store");
});
