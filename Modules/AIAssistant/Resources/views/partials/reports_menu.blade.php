@if (\Module::isActive('aiassistant'))
<li class="{{ \App\Misc\Helper::menuSelectedHtml('aiassistant-report') }}"><a href="{{ route('reports.agent') }}">AI Agent Report</a></li>
@endif
