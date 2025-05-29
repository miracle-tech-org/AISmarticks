<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\ChannelIdentifier\Http\Controllers'], function()
{
    Route::get('/', 'ChannelIdentifierController@index');
});
