@extends('layouts.app')

@section('title_full', "Reporting Details")

@section('content')
<div class="section-heading margin-bottom">
    {{$aiAgent->name}} Report
</div>
<br>
<br>

<div class="col-xs-12">
    <!-- Display AI Agent Report -->
    <div class="w-100">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <h3>Token Usage Over Time</h3>
                    <div class="chart-container" style="position: relative; height:40vh; width:80vw">
                        <canvas id="defaultBarChart" width="800" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <h3>Interaction Logs</h3>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Input Text</th>
                                <th>Output Text</th>
                                <th>Tokens Used</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($interactionLogs as $log)
                            <tr>
                                <td>{{ $log->id }}</td>
                                <td>{{ $log->input_text }}</td>
                                <td>{{ $log->output_text }}</td>
                                <td>{{ $log->tokens_used }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
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