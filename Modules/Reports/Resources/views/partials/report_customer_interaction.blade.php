<div class="rpt-metrics">

    <div class="rpt-metric">
        <div class="rpt-metric-title text-center">
            {{ __('Interaction count') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Number of customer interactions') }}"></i>
        </div>
        <div class="rpt-metric-value text-center">
            {{ $metrics['interactions_counts']['value'] }}
            @include('reports::partials/metric_change', ['change' => $metrics['interactions_counts']['change']])
        </div>
    </div>

    <div class="rpt-metric">
        <div class="rpt-metric-title text-center">
            {{ __('Agents Response time') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Agents Response time') }}"></i>
        </div>
        <div class="rpt-metric-value text-center">
            {{ $metrics['agent_responses_times']['value'] }}
            @include('reports::partials/metric_change', ['change' => $metrics['agent_responses_times']['change']])
        </div>
    </div>

    <div class="rpt-metric">
        <div class="rpt-metric-title text-center">
            {{ __('Avg. Agents Response Time') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Avg. Agents Response Time') }}"></i>
        </div>
        <div class="rpt-metric-value text-center">
            {{ $metrics['avg_agents_response_time']['value'] }}
            @include('reports::partials/metric_change', ['change' => $metrics['avg_agents_response_time']['change']])
        </div>
    </div>

    <div class="rpt-metric">
        <div class="rpt-metric-title text-center">
            {{ __('Resolution Times') }}
        </div>
        <div class="rpt-metric-value text-center">
            {{ $metrics['resolution_times']['value'] }}
            @include('reports::partials/metric_change', ['change' => $metrics['resolution_times']['change']])
        </div>
    </div>

</div>
<div class="rpt-metrics">

    <div class="rpt-metric">
        <div class="rpt-metric-title text-center">
            {{ __('Customers Response time') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Customers Response time') }}"></i>
        </div>
        <div class="rpt-metric-value text-center">
            {{ $metrics['customers_response_time']['value'] }}
            @include('reports::partials/metric_change', ['change' => $metrics['customers_response_time']['change']])
        </div>
    </div>

    <div class="rpt-metric">
        <div class="rpt-metric-title text-center">
            {{ __('Avg. Customers Response Time') }}&nbsp;<i class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="{{ __('Avg. Customers Response Time') }}"></i>
        </div>
        <div class="rpt-metric-value text-center">
            {{ $metrics['avg_customers_response_time']['value'] }}
            @include('reports::partials/metric_change', ['change' => $metrics['avg_customers_response_time']['change']])
        </div>
    </div>

</div>

<canvas id="rpt-chart" class="chart-js">

</canvas>


<div id="rpt-tables" data-menu-text="{{ __('Entries per page') }}" data-paging-text="{{ __('Showing _START_ to _END_ of _TOTAL_ ') }}" style="margin-top: 60px;">
    <div class="row">
        @if (count($table_customer_interaction))
            <div class="@if (count($table_customer_interaction)) col-md-12 @else col-md-12 @endif">
                <table class="table table-striped customer_interaction reports_datatable compact display">
                    <thead>
                    <tr>
                        <th>{{ __('Customer') }}</th>
                        <th  scope="col" data-toggle="tooltip" title="{{ __('Interactions counts') }}">
                            <span class="th-full">{{ __('Counts') }}</span>
                            <span class="th-medium">{{ __('Counts') }}</span>
                            <span class="th-small"><i class="glyphicon glyphicon-retweet"></i></span>
                        </th>
                        <th  scope="col" data-toggle="tooltip" title="{{ __('Agents Response time') }}">
                            <span class="th-full">{{ __('Agents Response time') }}</span>
                            <span class="th-medium">{{ __('Agents Resp. time') }}</span>
                            <span class="th-small"><i class="glyphicon glyphicon-comment"></i></span>
                        </th>
                        <th  scope="col" data-toggle="tooltip" title="{{ __('Resolution time') }}">
                            <span class="th-full">{{ __('Resolution time') }}</span>
                            <span class="th-medium">{{ __('Res. Time') }}</span>
                            <span class="th-small"><i class="glyphicon glyphicon-ok-circle"></i></span>
                        </th>
                        <th  scope="col" data-toggle="tooltip" title="{{ __('Count Agent threads') }}">
                            <span class="th-full">{{ __('Agent threads') }}</span>
                            <span class="th-medium">{{ __('Agent threads') }}</span>
                            <span class="th-small"><i class="glyphicon glyphicon-comment"></i></span>
                        </th>
                        <th  scope="col" data-toggle="tooltip" title="{{ __('Count Customer threads') }}">
                            <span class="th-full">{{ __('Customer threads') }}</span>
                            <span class="th-medium">{{ __('Customer threads') }}</span>
                            <span class="th-small"><i class="glyphicon glyphicon-comment"></i></span>
                        </th>

                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($table_customer_interaction as $customerId => $customer_interaction)
                        @php
                            $customerName = data_get($customer_interaction, 'name');
                            $customerName = trim($customerName) !== '' ? $customerName : __("Some customer");
                        @endphp
                        <tr>
                            <td><a href="{{ route('customers.update', ['id' => $customerId]) }}" target="_blank">{{ $customerName }}</a></td>
                            <td>{{ $customer_interaction['interaction_count'] }}</td>
                            <td>{{ $customer_interaction['agent_responses_times'] }}</td>
                            <td>{{ $customer_interaction['resolution_times'] }}</td>
                            <td>{{ $customer_interaction['agent_threads'] }}</td>
                            <td>{{ $customer_interaction['customer_threads'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>


                </table>
            </div>
        @endif
    </div>
</div>