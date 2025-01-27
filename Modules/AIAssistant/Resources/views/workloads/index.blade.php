@extends('layouts.app')

@section('title_full', 'Forwarded Tickets')
@section('sidebar')
@include('partials/sidebar_menu_toggle')
@include('mailboxes/sidebar_menu')
@endsection

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
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->thread->id }}</td>
                            <td>{{ $ticket->thread->body }}</td>
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
