@extends('layouts.app')

@section('title', __('Monday.com Pushboard Settings'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
<div class="section-heading">
    {{ __('Monday.com Pushboard Settings') }}
</div>

@include('mondayintegration::settings.partials.navigation')

<div class="row-container">
    <div class="col-md-12">
        <form action="{{ route('monday.settings.save', ['mailbox_id' => $mailbox->id]) }}" method="POST" class="form-horizontal">
            <input type="hidden" name="form_origin" value="pushboard">

            <!-- API Configuration -->
            <div class="form-section">
                <h3>{{ __('Pushboard Configuration') }}</h3>
                <p class="help-block">
                    {{ __('Configure the board to which data will be pushed from Smarticks.') }}
                </p>
                
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
                    <label class="col-sm-3 control-label">{{ __('Pushboard ID') }}</label>
                    <div class="col-sm-9">
                        <select name="pushboard_id" class="form-control" required>
                            <option value="">{{ __('Select Board') }}</option>
                            @foreach($boards['boards'] as $board)
                                <option value="{{ $board['id'] }}" 
                                    {{ (old('pushboard_id', $settings['pushboard_id'] ?? '') == $board['id']) ? 'selected' : '' }}>
                                    {{ $board['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <p class="help-block">
                            {{ __('Select the Monday.com board to push data to.') }}
                        </p>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-9">
                        <button type="button" class="btn btn-default test-board-connection">
                            {{ __('Test Board Connection') }}
                        </button>
                        <span class="connection-status"></span>
                    </div>
                </div>
            </div>

            <!-- Push Settings -->
            <div class="form-section">
                <h3>{{ __('Push Settings') }}</h3>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Push Triggers') }}</label>
                    <div class="col-sm-9">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="push_on_conversation_created" value="1" 
                                    {{ old('push_on_conversation_created', $settings['push_on_conversation_created'] ?? '') ? 'checked' : '' }}>
                                {{ __('When a new conversation is created') }}
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="push_on_conversation_assigned" value="1" 
                                    {{ old('push_on_conversation_assigned', $settings['push_on_conversation_assigned'] ?? '') ? 'checked' : '' }}>
                                {{ __('When a conversation is assigned') }}
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="push_on_conversation_status_changed" value="1" 
                                    {{ old('push_on_conversation_status_changed', $settings['push_on_conversation_status_changed'] ?? '') ? 'checked' : '' }}>
                                {{ __('When conversation status changes') }}
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="push_on_customer_created" value="1" 
                                    {{ old('push_on_customer_created', $settings['push_on_customer_created'] ?? '') ? 'checked' : '' }}>
                                {{ __('When a new customer is created') }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-9">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="push_attachments" value="1" 
                                    {{ old('push_attachments', $settings['push_attachments'] ?? '') ? 'checked' : '' }}>
                                {{ __('Push attachments') }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group margin-top">
                <div class="col-sm-offset-3 col-sm-9">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Save Pushboard Settings') }}
                    </button>
                    <a href="{{ route('monday.settings', ['mailbox_id' => $mailbox->id]) }}" class="btn btn-link">
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
        // Test board connection
        $('.test-board-connection').on('click', function() {
            var $button = $(this);
            var $status = $('.connection-status');
            var apiToken = $('input[name="api_token"]').val();
            var boardId = $('select[name="pushboard_id"]').val();
            
            if (!apiToken) {
                $status.removeClass('connection-success').addClass('connection-error').text('{{ __('API token is required') }}');
                return;
            }
            
            if (!boardId) {
                $status.removeClass('connection-success').addClass('connection-error').text('{{ __('Please select a board') }}');
                return;
            }
            
            $button.prop('disabled', true);
            $status.removeClass('connection-success connection-error').text('{{ __('Testing connection...') }}');
            
            $.ajax({
                url: '{{ route("monday.test-board-connection") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    api_token: apiToken,
                    board_id: boardId
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
