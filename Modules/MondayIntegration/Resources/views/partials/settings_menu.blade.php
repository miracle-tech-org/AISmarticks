@if (\Module::isActive('mondayintegration'))
<li @if (\Helper::isMenuSelected('monday.dashboard'))class="active"@endif><a href="{{ route('monday.dashboard', ['mailbox_id' => $mailbox->id]) }}"><i class="glyphicon glyphicon-erase"></i> Monday Dashboard</a></li>
<li @if (\Helper::isMenuSelected('monday.settings'))class="active"@endif><a href="{{ route('monday.settings', ['mailbox_id' => $mailbox->id]) }}"><i class="glyphicon glyphicon-erase"></i> Monday Settings</a></li>
<li @if (\Helper::isMenuSelected('monday.mappings'))class="active"@endif><a href="{{ route('monday.mappings', ['mailbox_id' => $mailbox->id]) }}"><i class="glyphicon glyphicon-erase"></i> Monday Field Mapping</a></li>
<li @if (\Helper::isMenuSelected('sidebar-widget'))class="active"@endif><a href="{{ route('sidebar-widget', ['mailbox_id' => $mailbox->id, 'conversation' => 1]) }}"><i class="glyphicon glyphicon-erase"></i> Monday Sidebar Widget</a></li>
@endif