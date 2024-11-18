@extends('layouts.app')

@section('title_full', "AIAssistant".' - '.$mailbox->name)

@section('sidebar')
@include('partials/sidebar_menu_toggle')
@include('mailboxes/sidebar_menu')
@endsection

@section('content')
<div class="section-heading margin-bottom">
    AI Assistant
</div>
<div class="col-xs-12">
    <a href="{{route('aiagents.create', ['mailbox_id'=>$mailbox->id])}}" class="btn btn-primary">Create</a>
</div>
<br>
<br>

<style>
    .table-striped > tbody > tr:nth-of-type(odd) {
        background-color: #232d53 !important;
    }
</style>



<div class="col-xs-12">
    @if (session('success'))
        <div class="alert alert-primary" role="alert" style="background-color: #2a3663;">
            {{ session('success') }}
        </div>
    @endif
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Model</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($aiAgents as $agent)
            <tr>
                <td>{{ $agent->id }}</td>
                <td>{{ $agent->name }}</td>
                <td>{{ $agent->model }}</td>
                <td>
                    <!-- Actions (Edit, Delete) -->
                    <a href="{{ route('aiagents.edit', ['mailbox_id'=>$mailbox->id, 'id' => $agent->id]) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form onsubmit="return confirm('Are you sure delete this')" action="{{ route('aiagents.destroy', ['mailbox_id'=>$mailbox->id, 'id' => $agent->id]) }}" method="POST"
                        style="display: inline-block">
                        {{ csrf_field() }}
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>

                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection