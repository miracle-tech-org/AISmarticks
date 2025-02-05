<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\AIAssistant\Http\Controllers'], function()
{
    Route::get('/ai-agents/session',                        'AIAssistantController@index')->name("aiagents.session");

    Route::get('/ai-assistant/limits',                      'AIAssistantController@showLimits')->name('ai-assistant.limits');
    Route::post('/ai-assistant/limits',                     'AIAssistantController@saveLimits')->name('ai-assistant.save-limits');
 
    Route::get("/ai-agents",                                'AIAgentController@index')->name("aiagents.index");
    Route::get("/ai-agents/create",                         'AIAgentController@create')->name("aiagents.create");
    Route::post("/ai-agents/store",                         'AIAgentController@store')->name("aiagents.store");
    Route::get("/ai-agents/edit/{id}",                      'AIAgentController@edit')->name("aiagents.edit");
    Route::post("/ai-agents/update/{id}",                   'AIAgentController@update')->name("aiagents.update");
    Route::post("/ai-agents/destory/{id}",                  'AIAgentController@destroy')->name("aiagents.destroy");

    Route::get('/ai-reports',                               "ReportingController@index")->name("reports.agent");
    Route::get('/ai-reports/{id}',                          "ReportingController@show")->name("reports.show");
    Route::get('/ai-reports/mailbox/{id}',                  "ReportingController@showMailbox")->name("reports.mailbox.show");
    
    Route::get('/ai-assignments/{id}',                      "AIAssignmentController@index")->name("assignments.index");
    Route::post('/ai-assignments/{id}/store',               "AIAssignmentController@store")->name("assignments.store");

    Route::get('/alert-test',                               "ReportingController@showTestAlert");
    Route::get('/init-daily-usage',                         "ReportingController@initDailyUsage");
    Route::get('/init-monthly-usage',                       "ReportingController@initMonthlyUsage");

    Route::get('/routing-rules',                           "RoutingRulesController@index")->name("routing.index");
    Route::get('/routing-rules/create',                    "RoutingRulesController@create")->name("routing.create");
    Route::post('/routing-rules/store',                    "RoutingRulesController@store")->name("routing.store");
    Route::get('/routing-rules/{id}/edit',                 "RoutingRulesController@edit")->name("routing.edit");
    Route::post('/routing-rules/{id}/update',              "RoutingRulesController@update")->name("routing.update");
    Route::post('/routing-rules/{id}/destroy',             "RoutingRulesController@destroy")->name("routing.destroy");

    Route::get('/specializations',                         "AgentSpecializationController@index")->name('specializations.index');
    Route::get('/specializations/{id}/show',               "AgentSpecializationController@show")->name('specializations.show');
    Route::get('/specializations/{id}/create',             "AgentSpecializationController@create")->name('specializations.create');
    Route::post('/specializations/{id}/store',             "AgentSpecializationController@store")->name('specializations.store');
    Route::get('/specializations/{id}/edit',               "AgentSpecializationController@edit")->name('specializations.edit');
    Route::post('/specializations/{id}/update',            "AgentSpecializationController@update")->name('specializations.update');
    Route::post('/specializations/{id}/destory',           "AgentSpecializationController@destroy")->name('specializations.destroy');

    Route::get('/workloads',                               "WorkloadController@index")->name('workloads.index');
});
