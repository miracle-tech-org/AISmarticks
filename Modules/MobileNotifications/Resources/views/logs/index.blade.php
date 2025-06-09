@extends('layouts.app') {{-- Assuming 'layouts.app' is your main layout --}}

@section('title', __('Notification Logs'))

@section('content')
<div class="container-fluid">
    <h1>{{ __('Notification Logs') }}</h1>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Platform') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Message Payload') }}</th>
                        <th>{{ __('Response') }}</th>
                        <th>{{ __('Sent At') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->user ? ($log->user->name ?: $log->user->email) : __('N/A') }}</td>
                            <td>{{ $log->deviceToken ? $log->deviceToken->platform : __('N/A') }}</td>
                            <td>{{ $log->status }}</td>
                            <td>{{ Str::limit(is_array($log->message_payload) ? json_encode($log->message_payload) : $log->message_payload, 50) }}</td>
                            <td>{{ Str::limit($log->response, 50) }}</td>
                            <td>{{ $log->sent_at ? $log->sent_at->format('Y-m-d H:i:s') : ($log->created_at ? $log->created_at->format('Y-m-d H:i:s') : __('N/A')) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">{{ __('No notification logs found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
