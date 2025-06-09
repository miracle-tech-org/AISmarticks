@extends('layouts.app') {{-- Assuming 'layouts.app' is your main layout --}}

@section('title', __('Registered Devices'))
@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Registered Devices') }}
    </div>

    <div class="row-container">
        <div class="col-md-12">
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
                            <td>{{ $device->user ? ($device->user->name ?: $device->user->email) : __('Unknown User') }}
                            </td>
                            <td>{{ $device->platform }}</td>
                            <td>{{ Str::limit($device->token, 30) }}</td> {{-- Using Str::limit for safety --}}
                            <td>{{ $device->created_at ? $device->created_at->format('Y-m-d H:i:s') : __('N/A') }}</td>
                            <td>
                                <button type="button" class="btn-delete btn btn-xs btn-danger"
                                    data-itemid="{{ $device->id }}">{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">{{ __('No devices registered yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($devices->hasPages())
                <div class="card-footer">
                    {{ $devices->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

<script>
    window.onload = function() {
        $(".btn-delete").click((e) => {
            if(!confirm("Are you sure you want to delete this token?")) {
                return;
            }

            const itemId = $(e.currentTarget).data("itemid");

            var data = {
                device_id: itemId
            }

            var url = "{{ route('mobilenotifications.devices.destroy') }}";

            $.ajax({
                url: url,
                type: "DELETE",
                data: data,
                success: function(response) {
                    showFloatingAlert('success', response);
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.error("Error:", error);
                }
            });
        })
    }
</script>
