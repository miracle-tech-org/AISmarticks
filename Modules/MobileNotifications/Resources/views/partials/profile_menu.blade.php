@if (\Module::isActive('mobilenotifications'))
<li @if (\Route::currentRouteName() == 'mobilenotifications.user.devices')class="active"@endif><a href="{{ route('mobilenotifications.user.devices') }}"><i class="glyphicon glyphicon-erase"></i> Mobile Devices</a></li>
<li @if (\Route::currentRouteName() == 'mobilenotifications.form')class="active"@endif><a href="{{ route('mobilenotifications.form', Auth::user()->id) }}"><i class="glyphicon glyphicon-erase"></i> Register Mobile Devices</a></li>
@endif