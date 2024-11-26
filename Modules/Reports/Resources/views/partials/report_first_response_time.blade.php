<div class="rpt-metrics">
    <div class="rpt-metric">
        <div class="rpt-metric-title text-center">
            {{ __('Time spent on the first response') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Measures the time taken for the first response to a customer query.') }}"></i>
        </div>
        <div class="rpt-metric-value text-center">
            {{ $metrics['responses_time']['value'] }}
            @include('reports::partials/metric_change', ['change' => $metrics['responses_time']['change']])
        </div>
    </div>

    <div class="rpt-metric">
        <div class="rpt-metric-title text-center">
            {{ __('Average time spent on the first response') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Shows overall average first response times.') }}"></i>
        </div>
        <div class="rpt-metric-value text-center">
            {{ $metrics['avg_agents_response_time']['value'] }}
            @include('reports::partials/metric_change', ['change' => $metrics['avg_agents_response_time']['change']])
        </div>
    </div>
</div>

<canvas id="rpt-chart" class="chart-js">

</canvas>


<div id="rpt-tables" data-menu-text="{{ __('Entries per page') }}" data-paging-text="{{ __('Showing _START_ to _END_ of _TOTAL_ ') }}" style="margin-top: 60px;">
    <div class="row">
        @if (count($table_agents))
            <div class="@if (count($table_agents)) col-md-12 @else col-md-8 col-md-offset-2 @endif">
                <table class="table table-striped first_response_time reports_datatable compact display">
                    <thead>
                    <tr>
                        <th  scope="col" data-toggle="tooltip" title="{{ __('Users') }}">
                            <span class="th-full">{{ __('Users') }}</span>
                            <span class="th-medium">{{ __('Users') }}</span>
                            <span class="th-small">{{ __('Users') }}</span>
                        </th>
                        <th  scope="col" data-toggle="tooltip" title="{{ __('Responses count') }}">
                            <span class="th-full">{{ __('Responses count') }}</span>
                            <span class="th-medium">{{ __('Count') }}</span>
                            <span class="th-small"><i class="glyphicon glyphicon-comment"></i></span>
                        </th>
                        <th  scope="col" data-toggle="tooltip" title="{{ __('First Response Time') }}">
                            <span class="th-full">{{ __('First Response Time') }}</span>
                            <span class="th-medium">{{ __('1st Resp. Time') }}</span>
                            <span class="th-small"><i class="glyphicon glyphicon-time"></i></span>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($table_agents as $agentId => $agent)
                        @php
                            $agentName = data_get($agent, 'name');
                            $agentName = trim($agentName) !== '' ? $agentName : __("Some user");
                        @endphp
                        <tr>
                            <td><a href="{{ route('users.profile', ['id' => $agentId]) }}" target="_blank">{{ $agentName }}</a></td>
                            <td>{{ $agent['countResp'] }}</td>
                            <td>{{ $agent['seconds'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>