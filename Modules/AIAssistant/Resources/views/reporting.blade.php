@extends('layouts.app')

@section('title_full', 'Reporting Dashboard')

@section('content')
    <div class="section-heading margin-bottom">
        Reporting Dashboard
    </div>
    <br>
    <br>

    <div class="col-xs-12">
        <div class="w-100">
            <div class="card-header">
                <h2>Reporting Dashboard</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h3>AI Agent Token Usage</h3>
                        <div class="chart-container" style="position: relative; height:40vh; width:80vw">
                            <canvas id="defaultBarChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h3>AI Agents</h3>
                        <ul>
                            @if (count($agents) > 0 && $agents[0]->id != '')
                                @foreach ($agents as $agent)
                                    <li>
                                        @if ($agent->assignments)
                                            <a href="{{ route('reports.show', ['id' => $agent->id]) }}">{{ $agent->name }} [Monthly Limit: {{$agent->assignments->monthly_usage_limit}} Monthly Usage: {{$agent->assignments->current_usage}} Daily Limit: {{$agent->assignments->daily_limit}} Daily Usage: {{$agent->assignments->daily_usage}} Replies: {{$agent->count}}]</a>
                                        @else
                                            <a href="{{ route('reports.show', ['id' => $agent->id]) }}">{{ $agent->name }} [Monthly Limit: 0 Monthly Usage: 0 Daily Limit: 0 Daily Usage: 0 Replies: 0]</a>
                                        @endif
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.onload = function() {
        const ctx = document.getElementById('defaultBarChart').getContext('2d');

        const agentsMonthlyTokens = <?php echo json_encode($agentsMonthlyTokens); ?>;

        const datasets = agentsMonthlyTokens.map(agent => {
            return {
                label: agent.name,
                data: agent.data,
                backgroundColor: `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 0.5)`,
                borderColor: `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 1)`,
                borderWidth: 1
            };
        });

        const myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                datasets: datasets
            },
            options: {
                responsive: true,
                title: {
                    display: true,
                    text: 'AI Agent Token Usage per Month'
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
</script>
