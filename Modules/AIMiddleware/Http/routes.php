<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\AIMiddleware\Http\Controllers'], function()
{
    Route::get('/', 'AIMiddlewareController@index');
});
