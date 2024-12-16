@extends('layouts.app')

@section('title_full', "AIAssignment".' - '.$mailbox->name)

@section('sidebar')
@include('partials/sidebar_menu_toggle')
@include('mailboxes/sidebar_menu')
@endsection

@section('content')
<div class="section-heading margin-bottom">
    AI Agent Assignment
</div>


<div class="col-xs-12">
    @if (session('success'))
        <div class="alert alert-primary" role="alert" style="background-color: #2a3663;">
            {{ session('success') }}
        </div>
    @endif
    <div>
        <form action="{{route('assignments.store', $mailbox->id)}}" method="post">
            <div class="form-group">
                <label for="mailbox_name">Mailbox Name</label>
                <input type="text" id="mailbox_name" class="form-control" value="{{$mailbox->name}}" readonly>
            </div>
            <div class="form-group">
                <label for="agent_id">AI Agents</label>
                <select class="form-control" name="agent_id" id="agent_id" required>
                    <option value="">Choose Agent</option>
                    @foreach ($aiAgents as $agent)
                    <option value="{{$agent->id}}">{{$agent->name}} ({{$agent->model}})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="response_mode">Response Mode</label>
                <select class="form-control" name="response_mode" id="response_mode" required>
                    <option value="suggest">Suggest</option>
                    <option value="auto">Auto</option>
                    <option value="both">Both</option>
                </select>
            </div>

            <div class="form-group">
                <label for="monthly_usage_limit">Monthly Usage Limit</label>
                @if (count($aiAssignment) > 0)
                <input type="text" name="monthly_usage_limit" id="monthly_usage_limit" class="form-control" value="{{$aiAssignment[0]->monthly_usage_limit}}">
                @else
                <input type="text" name="monthly_usage_limit" id="monthly_usage_limit" class="form-control" value="10000">
                @endif
            </div>

            <div class="form-group">
                <label for="daily_limit">Daily Limit</label>
                @if (count($aiAssignment) > 0)
                <input type="text" name="daily_limit" id="daily_limit" class="form-control" value="{{$aiAssignment[0]->daily_limit}}">
                @else
                <input type="text" name="daily_limit" id="daily_limit" class="form-control" value="350">
                @endif
            </div>

            <div class="form-group">
                <label for="current_usage">Current Usage of Month</label>
                @if (count($aiAssignment) > 0)
                <input type="text" name="current_usage" readonly id="current_usage" class="form-control" value="{{$aiAssignment[0]->current_usage}}">
                @else
                <input type="text" name="current_usage" readonly id="current_usage" class="form-control" value="0">
                @endif
            </div>

            <div class="form-group">
                <label for="daily_usage">Current Usage of Day</label>
                @if (count($aiAssignment) > 0)
                <input type="text" name="daily_usage" readonly id="daily_usage" class="form-control" value="{{$aiAssignment[0]->daily_usage}}">
                @else
                <input type="text" name="daily_usage" readonly id="daily_usage" class="form-control" value="0">
                @endif
            </div>

            <div class="form-group">
                <label for="usage_alert_threshold">Usage Alert Threshold of Month (%)</label>
                @if (count($aiAssignment) > 0)
                <input type="text" id="usage_alert_threshold" name="usage_alert_threshold" value="{{ $aiAssignment[0]->usage_alert_threshold }}" class="form-control">
                @else
                <input type="text" id="usage_alert_threshold" name="usage_alert_threshold" value="80" class="form-control">
                @endif
            </div>

            <div class="form-group">
                <label for="daily_usage_alert">Usage Alert Threshold of Day (%)</label>
                @if (count($aiAssignment) > 0)
                <input type="text" id="daily_usage_alert" name="daily_usage_alert" value="{{ $aiAssignment[0]->daily_usage_alert }}" class="form-control">
                @else
                <input type="text" id="daily_usage_alert" name="daily_usage_alert" value="80" class="form-control">
                @endif
            </div>

            <div class="form-group">
                <br>
                <button class="btn btn-success">Assign Agent</button>
            </div>
        </form>
    </div>
</div>
@endsection

@if (count($aiAssignment) > 0)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById("response_mode").value = @json($aiAssignment[0]->response_mode);
        document.getElementById("agent_id").value = @json($aiAssignment[0]->ai_agent_id);

        document.getElementById("agent_id").addEventListener("change", () => {
            const url = "{{route('assignments.get', $mailbox->id)}}";
            const agent_id = document.getElementById("agent_id").value;
            $.post(url, {agent_id}, (res) => {
                document.getElementById("monthly_usage_limit").value = res.monthly_usage_limit
                document.getElementById("daily_limit").value = res.daily_limit;
                document.getElementById("current_usage").value = res.current_usage;
                document.getElementById("daily_usage").value = res.daily_usage;
                document.getElementById("usage_alert_threshold").value = res.usage_alert_threshold;
                document.getElementById("daily_usage_alert").value = res.daily_usage_alert;
                document.getElementById("response_mode").value = res.response_mode
            })
        })
    });
</script>
@endif