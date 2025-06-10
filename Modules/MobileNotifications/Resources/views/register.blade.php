@extends('layouts.app')

@section('title', __('Register Device'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('users/sidebar_menu')
@endsection

@section('content')
<div class="section-heading">
    {{ __('Use the form below on your mobile device to register it for push notifications.') }}
</div>
<div class="container"> {{-- Or your typical content wrapper --}}
    <div class="row">
        <div class="col-md-12"> {{-- Adjust column sizing as per your layout --}}
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
                <input type="text" name="token" id="token" class="form-control" required>
            </div>
            <button class="btn btn-primary" type="button" id="btn-submit">{{ __('Register') }}</button>
        </div>
    </div>
</div>
@endsection

<script>
    window.onload = function() {
        $("#btn-submit").on('click', function() {
            var url = `{{route('mobilenotifications.register')}}`;
            var token = $("#token").val();
            if(token == '') {
                showFloatingAlert('error', 'Please input device token');
                return;
            }
            var data = {
                platform: $("#platform").val(),
                token
            }
            $.ajax({
                url: url,
                type: "POST",
                data: data,
                success: function(response) {
                    showFloatingAlert('success', response);
                },
                error: function(xhr, status, error) {
                    console.error("Error:", error);
                }
            });
        })
    }
</script>
