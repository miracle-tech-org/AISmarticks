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
                <label for="name">Mailbox Name</label>
                <input type="text" class="form-control" value="{{$mailbox->name}}" readonly>
            </div>
            <div class="form-group">
                <label for="name">AI Agents</label>
                <select class="form-control" name="agent_id" id="agent_id" required>
                    <option value="">Choose Agent</option>
                    @foreach ($aiAgents as $agent)
                    <option value="{{$agent->id}}">{{$agent->name}} ({{$agent->model}})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="name">Response Mode</label>
                <select class="form-control" name="response_mode" id="response_mode" required>
                    <option value="suggest">Suggest</option>
                    <option value="auto">Auto</option>
                    <option value="both">Both</option>
                </select>
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
    });
</script>
@endif