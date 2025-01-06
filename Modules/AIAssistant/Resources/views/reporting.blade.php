@extends('layouts.app')

@section('title_full', "Reporting Dashboard")

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
                    <h3>Mailboxes</h3>
                    <ul>
                    @foreach ($agentsByMailbox as $mailboxName => $agents)
                        @php
                            $totalToken = 0;
                            $totalConv = 0;
                            
                            if(count($agents) > 0) {
                                foreach ($agents as $agent) {
                                    $totalToken += ($agent->tokens_used ?? 0);
                                    $totalConv += $agent->conversations;
                                }
                            }

                        @endphp
                        <li>
                            <a href="{{route('reports.mailbox.show', $agents[0]->mailbox_id)}}">
                                <h4>{{ $mailboxName }} [Conversations: {{ $totalConv }} Tokens: {{ $totalToken }} Total Replies: {{ $totalConv }} Average of Reply: {{ $totalConv > 0 ? $totalConv / $totalConv : 0 }}]</h4>
                            </a>
                            @if(count($agents) > 0 && $agents[0]->id != '')
                                <ul>
                                    @foreach ($agents as $agent)
                                        <li>
                                            <a href="{{ route('reports.show', ['id' => $agent->id]) }}">{{ $agent->ai_agent_name }} [Conversations: {{$agent->conversations}}, Tokens: {{$agent->tokens_used ?? 0}} Total Replies: {{ $agent->conversations }} Average of Reply: {{ $agent->conversations > 0 ? $agent->conversations / $agent->conversations : 0 }}]</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.onload = function () {
        const ctx = document.getElementById('defaultBarChart').getContext('2d');

        const graphData = <?php echo json_encode($graphData); ?>;
        const months = <?php echo json_encode($months); ?>;

        const datasets = graphData.map(item => ({
            label: item.label,
            data: item.data,
            backgroundColor: `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 0.5)`,
            borderColor: `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 1)`,
            borderWidth: 1
        }));

        const myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: datasets,
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'AI Agent Token Usage by Mailbox and Month'
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
            },
        });
    };
</script>
