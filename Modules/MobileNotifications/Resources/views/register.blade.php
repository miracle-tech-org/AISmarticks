@extends('layouts.app')

@section('title', __('Register Device'))

@section('content')
<div class="section">
    <p>{{ __('Use the form below on your mobile device to register it for push notifications.') }}</p>
    <form method="POST" action="{{ route('mobilenotifications.register') }}">
        @csrf
        <div class="form-group">
            <label for="platform">{{ __('Platform') }}</label>
            <select name="platform" id="platform" class="form-control" required>
                <option value="Android">{{ __('Android') }}</option>
                <option value="iOS">{{ __('iOS') }}</option>
                <option value="Web">{{ __('Web') }}</option>
            </select>
        </div>
        <div class="form-group">
            <label>{{ __('Device Token') }}</label>
            <input type="text" name="token" class="form-control" required>
        </div>
        <button class="btn btn-primary" type="submit">{{ __('Register') }}</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const platformSelect = document.querySelector('select[name="platform"]');
        if (platformSelect) {
            const ua = navigator.userAgent || navigator.vendor || window.opera;
            if (/android/i.test(ua)) {
                platformSelect.value = 'Android';
            } else if (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) {
                platformSelect.value = 'iOS';
            } else {
                // Default or could be 'Web' if that's a target
                platformSelect.value = 'Web';
            }
        }
    });
</script>
@endpush
