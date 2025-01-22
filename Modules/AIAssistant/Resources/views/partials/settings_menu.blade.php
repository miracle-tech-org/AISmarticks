@if (\Module::isActive('aiassistant'))
<li @if (\Helper::isMenuSelected('aiassistant'))class="active"@endif><a href="{{ route('aiagents.index', ['mailbox_id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-erase"></i> AI Assistant</a></li>
<li @if (\Helper::isMenuSelected('aiassistant-assignments'))class="active"@endif><a href="{{ route('assignments.index', ['mailbox_id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-erase"></i> AI Agent Assignment</a></li>
<li @if (\Helper::isMenuSelected('aiassistant-routing-rule'))class="active"@endif><a href="{{ route('routing.index') }}"><i class="glyphicon glyphicon-erase"></i> Routing Rules</a></li>
<li @if (\Helper::isMenuSelected('aiassistant-specializations'))class="active"@endif><a href="{{ route('specializations.index') }}"><i class="glyphicon glyphicon-erase"></i> Agent Specializations</a></li>
@endif
