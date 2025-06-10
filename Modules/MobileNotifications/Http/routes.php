<?php

Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['admin'], 'prefix' => Helper::getSubdirectory(), 'namespace' => 'Modules\MobileNotifications\Http\Controllers'], function () {
    Route::get('/mailbox/{mailbox_id}/mobile-notifications/settings', 'SettingsController@index')->name('mobilenotifications.settings');
    Route::get('/mailbox/{mailbox_id}/mobile-notifications/devices', 'DeviceManagementController@index')->name('mobilenotifications.devices.index');
    Route::get('/mailbox/{mailbox_id}/mobile-notifications/logs', 'NotificationLogController@index')->name('mobilenotifications.logs.index');

    Route::post('/mobile-notifications/settings', 'SettingsController@save')->name('mobilenotifications.settings.save');
    Route::post('/mobile-notifications/test-notification', 'NotificationController@test')->name('mobilenotifications.test');
    Route::delete('/mobile-notifications/devices/delete', 'DeviceManagementController@destroy')->name('mobilenotifications.devices.destroy');
});

Route::group(['middleware' => ['web', 'auth'], 'prefix' => Helper::getSubdirectory(), 'namespace' => 'Modules\MobileNotifications\Http\Controllers'], function () {
    Route::get('/user/mobile-devices', 'UserDeviceController@index')->name('mobilenotifications.user.devices');
    Route::get('/mobile-notifications/register/{user}', 'RegistrationController@form')->name('mobilenotifications.form');
    Route::post('/mobile-notifications/register-device', 'RegistrationController@register')->name('mobilenotifications.register');
        // ->middleware('throttle:mobilenotifications_register_device'); // Commented out due to RateLimiter facade not available in L5.5
    Route::post('/user/mobile-devices/preferences', 'UserDeviceController@updatePreferences')->name('mobilenotifications.user.preferences.update');
    Route::post('/mobile-notifications/device/delete', 'RegistrationController@delete')->name('mobilenotifications.device.delete');
});
