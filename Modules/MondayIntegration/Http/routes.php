<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\MondayIntegration\Http\Controllers'], function()
{
    Route::get('/', 'MondayIntegrationController@index');
});
