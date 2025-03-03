@if (\Module::isActive('mondayintegration'))
<li @if (\Helper::isMenuSelected('monday.dashboard'))class="active"@endif><a href="{{ route('monday.dashboard', ['mailbox_id' => $mailbox->id]) }}"><i class="glyphicon glyphicon-erase"></i> Monday Dashboard</a></li>
<li @if (\Helper::isMenuSelected('monday.settings'))class="active"@endif><a href="{{ route('monday.settings', ['mailbox_id' => $mailbox->id]) }}"><i class="glyphicon glyphicon-erase"></i> Monday Settings</a></li>
@endif