@if (\Module::isActive('mobilenotifications'))
<li @if (\Helper::isMenuSelected('mobilenotifications.settings'))class="active"@endif><a href="{{ route('mobilenotifications.settings', ['mailbox_id' => $mailbox->id]) }}"><i class="glyphicon glyphicon-erase"></i> Mobile Notification</a></li>
<li @if (\Helper::isMenuSelected('mobilenotifications.devices.index'))class="active"@endif><a href="{{ route('mobilenotifications.devices.index', ['mailbox_id' => $mailbox->id]) }}"><i class="glyphicon glyphicon-erase"></i> Mobile Devices</a></li>
<li @if (\Helper::isMenuSelected('mobilenotifications.logs.index'))class="active"@endif><a href="{{ route('mobilenotifications.logs.index', ['mailbox_id' => $mailbox->id]) }}"><i class="glyphicon glyphicon-erase"></i> Notification Logs</a></li>
@endif