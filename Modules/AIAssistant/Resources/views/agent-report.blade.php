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
            <style>
                table {
                    table-layout: fixed; /* Fix table layout */
                    width: 100%; /* Ensure the table takes up the full width */
                }
                td, th {
                    word-wrap: break-word; /* Allow wrapping of long strings */
                    white-space: normal; /* Override nowrap to allow text wrapping */
                }
                .fixed-width {
                    width: 200px; /* Set a specific width for the fixed column */
                }
            </style>
            <div class="row">
                <div class="col-md-12">
                    <h3>Interaction Logs</h3>
                    <h3 style="text-align: center">Conversations: {{$conversationCount}} &nbsp;&nbsp;&nbsp;Interactions: {{count($interactionLogs)}} &nbsp;&nbsp;&nbsp;Replies: {{count($interactionLogs)}}</h3>
                    <div class="table-responsive" style="padding: 1%">
                        <table class="table table-striped" id="reportTable">
                            <thead>
                                <tr>
                                    <th width="5">ID</th>
                                    <th width="40">Input Text</th>
                                    <th width="40">Output Text</th>
                                    <th width="5">Tokens Used</th>
                                    <th width="5">DateTime</th>
                                    <th width="5">Interactions</th>
                                    <th width="5">Messages</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = 1; @endphp
                                @foreach ($interactionLogs as $log)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td class="fixed-width">{{ $log->input_text }}</td>
                                    <td class="fixed-width">{{ $log->output_text }}</td>
                                    <td>{{ $log->tokens_used }}</td>
                                    <td>{{ $log->created_at }}</td>
                                    <td>1</td>
                                    <td>{{ substr_count($log->output_text, '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>


<script>
    window.onload = function() {
        const ctx = document.getElementById('defaultBarChart').getContext('2d');
        new DataTable(document.getElementById("reportTable"));

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