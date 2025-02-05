@extends('layouts.app')

@section('title_full', 'AIAssistant')

@section('content')
    <div class="section-heading margin-bottom">
        AI Assistant
    </div>
    <div class="col-xs-12">
        <a href="{{ route('aiagents.create') }}" class="btn btn-primary">Create</a>
    </div>
    <br>
    <br>

    <div class="col-xs-12">
        @if (session('success'))
            <div class="alert alert-primary" role="alert">
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
                            <a href="{{ route('aiagents.edit', $agent->id) }}" class="btn btn-sm btn-primary">Edit</a>
                            <form action="{{ route('aiagents.destroy', $agent->id) }}" method="POST"
                                style="display: inline-block">
                                {{ csrf_field() }}
                                <button type="submit" class="delete-btn btn btn-sm btn-danger">Delete</button>
                            </form>

                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

<script>
    window.onload = function() {
        const deleteButtons = document.querySelectorAll('.delete-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                if (!confirm('Are you sure you want to delete this agent?')) {
                    event.preventDefault();
                }
            });
        });
    }
</script>
