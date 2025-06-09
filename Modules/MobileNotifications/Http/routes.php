<?php

Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['admin'], 'prefix' => Helper::getSubdirectory(), 'namespace' => 'Modules\MobileNotifications\Http\Controllers'], function () {
    Route::get('/mobile-notifications/settings', 'SettingsController@index')->name('mobilenotifications.settings');
    Route::post('/mobile-notifications/settings', 'SettingsController@save')->name('mobilenotifications.settings.save');
    Route::post('/mobile-notifications/test-notification', 'NotificationController@test')->name('mobilenotifications.test');
    Route::get('/mobile-notifications/devices', 'DeviceManagementController@index')->name('mobilenotifications.devices.index');
    Route::delete('/mobile-notifications/devices/{device}', 'DeviceManagementController@destroy')->name('mobilenotifications.devices.destroy');
    Route::get('/mobile-notifications/logs', 'NotificationLogController@index')->name('mobilenotifications.logs.index');
});

Route::group(['middleware' => ['web', 'auth'], 'prefix' => Helper::getSubdirectory(), 'namespace' => 'Modules\MobileNotifications\Http\Controllers'], function () {
    Route::get('/user/mobile-devices', 'UserDeviceController@index')->name('mobilenotifications.user.devices');
    Route::get('/mobile-notifications/register/{user}', 'RegistrationController@form')->name('mobilenotifications.form')->middleware('signed');
    Route::post('/mobile-notifications/register-device', 'RegistrationController@register')
        ->name('mobilenotifications.register');
        // ->middleware('throttle:mobilenotifications_register_device'); // Commented out due to RateLimiter facade not available in L5.5
    Route::post('/user/mobile-devices/preferences', 'UserDeviceController@updatePreferences')->name('mobilenotifications.user.preferences.update');
    Route::delete('/mobile-notifications/device/{id}', 'RegistrationController@delete')->name('mobilenotifications.device.delete');
});
