@if (\Module::isActive('aiassistant'))
<li @if (\Helper::isMenuSelected('aiassistant-routing-rule'))class="active"@endif><a href="{{ route('routing.index') }}">AI Routing Rules</a></li>
<li @if (\Helper::isMenuSelected('aiassistant-specializations'))class="active"@endif><a href="{{ route('specializations.index') }}">AI Agent Specializations</a></li>
<li @if (\Helper::isMenuSelected('aiassistant-workloads'))class="active"@endif><a href="{{ route('workloads.index') }}">AI Workloads</a></li>
@endif