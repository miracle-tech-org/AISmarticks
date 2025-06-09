@extends('layouts.app') {{-- Assuming 'layouts.app' is your main layout --}}

@section('title', __('Registered Devices'))

@section('content')
<div class="container-fluid">
    <h1>{{ __('Registered Devices') }}</h1>

    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Platform') }}</th>
                        <th>{{ __('Token (Partial)') }}</th>
                        <th>{{ __('Registered At') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        <tr>
                            <td>{{ $device->user ? ($device->user->name ?: $device->user->email) : __('Unknown User') }}</td>
                            <td>{{ $device->platform }}</td>
                            <td>{{ Str::limit($device->token, 30) }}</td> {{-- Using Str::limit for safety --}}
                            <td>{{ $device->created_at ? $device->created_at->format('Y-m-d H:i:s') : __('N/A') }}</td>
                            <td>
                                <form action="{{ route('mobilenotifications.devices.destroy', $device->id) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to delete this token?') }}');" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">{{ __('No devices registered yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($devices->hasPages())
            <div class="card-footer">
                {{ $devices->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
