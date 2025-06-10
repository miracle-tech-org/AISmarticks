@extends('layouts.app') {{-- Assuming 'layouts.app' is your main layout --}}

@section('title', __('My Registered Mobile Devices'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('users/sidebar_menu')
@endsection

@section('content')
<div class="container"> {{-- Or your typical content wrapper --}}
    <div class="row">
        <div class="col-md-12"> {{-- Adjust column sizing as per your layout --}}

            <h2>{{ __('My Registered Mobile Devices') }}</h2>
            <hr>

            @if($devices->isEmpty())
                <p>{{ __('You have no registered mobile devices.') }}</p>
                <p>{{ __('To add a new device, please contact an administrator to send you a registration link via your user profile page.') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Platform') }}</th>
                                <th>{{ __('Registered At') }}</th>
                                <th>{{ __('Token Hint (Optional)') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($devices as $device)
                                <tr>
                                    <td>{{ $device->platform ?? __('N/A') }}</td>
                                    <td>{{ $device->created_at ? $device->created_at->format(config('app.datetime_format', 'Y-m-d H:i:s')) : __('N/A') }}</td>
                                    <td>{{ Str::limit($device->token, 15) }}...</td>
                                    <td>
                                        <button data-itemid="{{$device->id}}" type="button" class="btn-delete btn btn-sm btn-danger">{{ __('Remove Device') }}</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <hr>
                <p>{{ __('To add a new device, please contact an administrator to send you a registration link via your user profile page.') }}</p>
            @endif

            <hr style="margin-top: 30px; margin-bottom: 30px;">

            <h2>{{ __('Mobile Notification Preferences') }}</h2>
            <form method="POST" action="{{ route('mobilenotifications.user.preferences.update') }}">
                @if(!empty($preferencesData))
                    @foreach ($preferencesData as $preference)
                        <div class="form-check" style="margin-bottom: 10px;">
                            <input class="form-check-input" type="checkbox" name="preferences[{{ $preference['key'] }}]" id="pref_{{ $preference['key'] }}" value="1" {{ $preference['is_enabled'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="pref_{{ $preference['key'] }}" style="font-weight: normal; margin-left: 5px;">
                                {{ $preference['label'] }}
                            </label>
                            {{-- You can add description here if available in $preference['description'] --}}
                            {{-- <small class="form-text text-muted">{{ $preference['description'] ?? '' }}</small> --}}
                        </div>
                    @endforeach
                    <button type="submit" class="btn btn-primary" style="margin-top: 15px;">{{ __('Save Preferences') }}</button>
                @else
                    <p>{{ __('No notification types available to configure.') }}</p>
                @endif
            </form>

            {{-- Example link to go back to a general profile page --}}

        </div>
    </div>
</div>
@endsection

<script>
    window.onload = function() {
        $(".btn-delete").click((e) => {
            if(!confirm("Are you sure you want to delete this?")) {
                return;
            }

            const itemId = $(e.currentTarget).data("itemid");

            var data = {
                id: itemId
            }

            var url = "{{ route('mobilenotifications.device.delete') }}";
            $.ajax({
                url: url,
                type: "POST",
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
