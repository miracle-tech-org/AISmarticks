@extends('layouts.app')

@section('title_full', 'Forwarded Tickets')

@section('content')
    <div class="section-heading margin-bottom">
        {{ __('Forwarded Tickets') }}
    </div>
    <br>

    <div class="container">
        <div class="rules-container">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>User Query</th>
                        <th>AI Response</th>
                        <th>Tags Applied</th>
                        <th>Action</th>
                        <th>Rating</th>
                        <th>Date</th>

                    </tr>
                </thead>
                <tbody>
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->thread->id }}</td>
                            <td>{{ $ticket->thread->body }}</td>
                            <td>{{ json_decode($ticket->review)->reason }}</td>
                            <td>{{ 'needs-human' }}</td>
                            <td>{{ 'addTag' }}</td>
                            <td>☆☆☆☆☆</td>
                            <td>{{ $ticket->created_at }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection

<script>
    window.onload = function() {
        const deleteButtons = document.querySelectorAll('.delete-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                if (!confirm('Are you sure you want to delete this routing rule?')) {
                    event.preventDefault();
                }
            });
        });
    }
</script>
