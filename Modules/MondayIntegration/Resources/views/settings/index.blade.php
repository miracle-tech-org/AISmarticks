@extends('layouts.app')

@section('title', __('Monday.com Settings'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
<div class="section-heading">
    {{ __('Monday.com Integration Settings') }}
</div>

@include('mondayintegration::settings.partials.navigation')

<div class="row-container">
    <div class="col-md-12">
        <form action="{{ route('monday.settings.save', ['mailbox_id' => $mailbox->id]) }}" method="POST" class="form-horizontal">
            <input type="hidden" name="form_origin" value="general">

            <!-- API Configuration -->
            <div class="form-section">
                <h3>{{ __('API Configuration') }}</h3>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('API Token') }}</label>
                    <div class="col-sm-9">
                        <input type="password" name="api_token" class="form-control" 
                               value="{{ old('api_token', $settings['api_token'] ?? '') }}" required>
                        <p class="help-block">
                            {{ __('Your Monday.com API token. Get it from your Monday.com account settings.') }}
                        </p>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-9">
                        <button type="button" class="btn btn-default test-connection">
                            {{ __('Test API Connection') }}
                        </button>
                        <span class="connection-status"></span>
                    </div>
                </div>
            </div>

            <!-- Dual-Board Architecture Info -->
            <div class="form-section">
                <h3>{{ __('Dual-Board Architecture') }}</h3>
                <div class="alert alert-info">
                    <p>
                        {{ __('This integration uses a dual-board architecture:') }}
                    </p>
                    <ul>
                        <li><strong>{{ __('Pullboard:') }}</strong> {{ __('The board from which data is pulled into Smarticks') }}</li>
                        <li><strong>{{ __('Pushboard:') }}</strong> {{ __('The board to which data is pushed from Smarticks') }}</li>
                    </ul>
                    <p>
                        {{ __('Configure each board separately using the tabs above.') }}
                    </p>
                </div>
            </div>

            <!-- Sync Settings -->
            <div class="form-section">
                <h3>{{ __('General Sync Settings') }}</h3>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Sync Frequency') }}</label>
                    <div class="col-sm-9">
                        <select name="sync_frequency" class="form-control">
                            <option value="5" {{ old('sync_frequency', $settings['sync_frequency'] ?? '') == '5' ? 'selected' : '' }}>
                                {{ __('Every 5 minutes') }}
                            </option>
                            <option value="15" {{ old('sync_frequency', $settings['sync_frequency'] ?? '') == '15' ? 'selected' : '' }}>
                                {{ __('Every 15 minutes') }}
                            </option>
                            <option value="30" {{ old('sync_frequency', $settings['sync_frequency'] ?? '') == '30' ? 'selected' : '' }}>
                                {{ __('Every 30 minutes') }}
                            </option>
                            <option value="60" {{ old('sync_frequency', $settings['sync_frequency'] ?? '') == '60' ? 'selected' : '' }}>
                                {{ __('Every hour') }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="form-section">
                <h3>{{ __('Notification Settings') }}</h3>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Error Notifications') }}</label>
                    <div class="col-sm-9">
                        <select name="error_notification" class="form-control">
                            <option value="none" {{ old('error_notification', $settings['error_notification'] ?? '') == 'none' ? 'selected' : '' }}>
                                {{ __('Disabled') }}
                            </option>
                            <option value="immediate" {{ old('error_notification', $settings['error_notification'] ?? '') == 'immediate' ? 'selected' : '' }}>
                                {{ __('Immediate') }}
                            </option>
                            <option value="daily" {{ old('error_notification', $settings['error_notification'] ?? '') == 'daily' ? 'selected' : '' }}>
                                {{ __('Daily Summary') }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Notification Email') }}</label>
                    <div class="col-sm-9">
                        <input type="email" name="notification_email" class="form-control" 
                               value="{{ old('notification_email', $settings['notification_email'] ?? '') }}">
                        <p class="help-block">
                            {{ __('Email address for error notifications. Leave empty to use system default.') }}
                        </p>
                    </div>
                </div>

                <div class="form-group margin-top">
                    <div class="col-sm-offset-3 col-sm-9">
                        <a href="{{route('monday.settings.clear')}}" class="btn btn-primary">
                            {{ __('Clear Mappings') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Advanced Settings -->
            <div class="form-section">
                <h3>{{ __('Advanced Settings') }}</h3>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('API Rate Limit') }}</label>
                    <div class="col-sm-9">
                        <input type="number" name="rate_limit" class="form-control" 
                               value="{{ old('rate_limit', $settings['rate_limit'] ?? '100') }}" min="1" max="1000">
                        <p class="help-block">
                            {{ __('Maximum API requests per minute. Default: 100') }}
                        </p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Retry Attempts') }}</label>
                    <div class="col-sm-9">
                        <input type="number" name="retry_attempts" class="form-control" 
                               value="{{ old('retry_attempts', $settings['retry_attempts'] ?? '3') }}" min="0" max="10">
                        <p class="help-block">
                            {{ __('Number of retry attempts for failed operations. Default: 3') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="form-group margin-top">
                <div class="col-sm-offset-3 col-sm-9">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Save Settings') }}
                    </button>
                    <a href="{{ route('monday.dashboard', ['mailbox_id' => $mailbox->id]) }}" class="btn btn-link">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

@push('css')
<style>
    .form-section {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .form-section h3 {
        margin-top: 0;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .margin-top {
        margin-top: 20px;
    }

    .help-block {
        font-size: 12px;
        color: #777;
    }

    .connection-status {
        margin-left: 10px;
        font-weight: bold;
    }

    .connection-success {
        color: #5cb85c;
    }

    .connection-error {
        color: #d9534f;
    }
</style>
@endpush
@endsection

<script>
    window.onload = function() {
        // Test API connection
        $('.test-connection').on('click', function() {
            var $button = $(this);
            var $status = $('.connection-status');
            var apiToken = $('input[name="api_token"]').val();
            
            if (!apiToken) {
                $status.removeClass('connection-success').addClass('connection-error').text('{{ __('API token is required') }}');
                return;
            }
            
            $button.prop('disabled', true);
            $status.removeClass('connection-success connection-error').text('{{ __('Testing connection...') }}');
            
            $.ajax({
                url: '{{ route("monday.test-connection") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    api_token: apiToken
                },
                success: function(response) {
                    $status.removeClass('connection-error').addClass('connection-success').text(response.message);
                },
                error: function(xhr) {
                    var errorMessage = '{{ __('Connection failed') }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    $status.removeClass('connection-success').addClass('connection-error').text(errorMessage);
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
    };
</script>