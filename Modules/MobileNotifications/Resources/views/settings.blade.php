@extends('layouts.app')

@section('title', __('Mobile Notifications'))
@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
<div class="section-heading">
    {{ __('MobileNotification Settings') }}
</div>

<div class="row-container">
    <div class="col-md-12">
        <form method="POST" enctype="multipart/form-data" action="{{ route('mobilenotifications.settings.save') }}">
            <div class="form-group">
                <label>{{ __('Project ID') }}</label>
                <input type="text" name="project_id" class="form-control" value="{{ old('project_id', $settings['project_id']) }}" required>
            </div>
            <div class="form-group">
                <label>{{ __('Server Name') }}</label>
                <input type="text" name="server_name" class="form-control" value="{{ old('server_name', $settings['server_name']) }}" required>
            </div>
            <div class="form-group">
                <label>{{ __('Default Title') }}</label>
                <input type="text" name="default_title" class="form-control" value="{{ old('default_title', $settings['default_title']) }}">
            </div>
            <div class="form-group">
                <label>{{ __('Firebase Credentials') }} (.json file)</label>
                <input type="file" name="firebase_credentials" class="form-control">
                @if (!empty($settings['firebase_credentials_path']))
                    <small class="form-text text-muted">{{ __('Current file: :path', ['path' => $settings['firebase_credentials_path']]) }}</small>
                @endif
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="enabled" value="1" {{ $settings['enabled'] ? 'checked' : '' }}> {{ __('Enable Push') }}</label>
            </div>
            <button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
            <button type="button" id="sendTestNotification" class="btn btn-info">{{ __('Send Test Notification') }}</button>
        </form>
    </div>
</div>
@endsection

<script>

window.onload = function() {
    $("#sendTestNotification").click(() => {
        fetch("{{ route('mobilenotifications.test') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                // body: JSON.stringify({}) // No body needed for this request as user is taken from auth
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                }
                throw new Error('Network response was not ok.');
            })
            .then(data => {
                if (data.success === true) {
                    alert('Test notification sent (simulated). Check logs.');
                } else {
                    alert('Failed to send test notification. Server indicated failure. Check console/logs.');
                }
            })
            .catch(error => {
                console.error('Error sending test notification:', error);
                alert('Failed to send test notification. Check console/logs.');
            });
    })
}
</script>
