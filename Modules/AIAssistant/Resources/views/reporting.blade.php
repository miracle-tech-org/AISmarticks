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
                                <h4>{{ $mailboxName }} [Conversations: {{$totalConv}} Tokens: {{$totalToken}}] </h4>
                            </a>
                            @if(count($agents) > 0 && $agents[0]->id != '')
                                <ul>
                                    @foreach ($agents as $agent)
                                        <li>
                                            <a href="{{ route('reports.show', ['id' => $agent->id]) }}">{{ $agent->ai_agent_name }} [Conversations: {{$agent->conversations}}, Tokens: {{$agent->tokens_used ?? 0}}]</a>
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
    window.onload = function() {

        const ctx = document.getElementById('defaultBarChart').getContext('2d');

        const myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'Septemger', 'October', 'November', 'December'],
                datasets: [{
                    label: 'Tokens Used',
                    data: <?php echo json_encode($monthlyTokens); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                title: {
                    display: true,
                    text: 'AI Agent Token Usage'
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