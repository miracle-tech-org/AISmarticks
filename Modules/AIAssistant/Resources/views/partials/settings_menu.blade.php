@if (\Module::isActive('aiassistant'))
<li @if (\Helper::isMenuSelected('aiassistant-assignments'))class="active"@endif><a href="{{ route('assignments.index', ['mailbox_id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-erase"></i> AI Agent Assignment</a></li>
@endif
