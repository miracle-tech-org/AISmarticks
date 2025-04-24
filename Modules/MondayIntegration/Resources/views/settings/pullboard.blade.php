@extends('layouts.app')

@section('title', __('Monday.com Pullboard Settings'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
<div class="section-heading">
    {{ __('Monday.com Pullboard Settings') }}
</div>

@include('mondayintegration::settings.partials.navigation')

<div class="row-container">
    <div class="col-md-12">
        <form action="{{ route('monday.settings.save', ['mailbox_id' => $mailbox->id]) }}" method="POST" class="form-horizontal">
            <input type="hidden" name="form_origin" value="pullboard">

            <!-- API Configuration -->
            <div class="form-section">
                <h3>{{ __('Pullboard Configuration') }}</h3>
                <p class="help-block">
                    {{ __('Configure the board from which data will be pulled into Smarticks.') }}
                </p>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Monday Name') }}</label>
                    <div class="col-sm-9">
                        <input type="hidden" name="api_token" class="form-control" 
                               value="{{ old('api_token', $settings['api_token'] ?? '') }}" required>
                        <input type="text" value="{{$mondayName}}" class="form-control" readonly/>
                        <p class="help-block">
                            {{ __('Your Monday.com API token. Get it from your Monday.com account settings.') }}
                        </p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Pullboard ID') }}</label>
                    <div class="col-sm-9">
                        <select name="pullboard_id" class="form-control" required>
                            <option value="">{{ __('Select Board') }}</option>
                            @foreach($boards['boards'] as $board)
                                <option value="{{ $board['id'] }}" 
                                    {{ (old('pullboard_id', $settings['pullboard_id'] ?? '') == $board['id']) ? 'selected' : '' }}>
                                    {{ $board['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <p class="help-block">
                            {{ __('Select the Monday.com board to pull data from.') }}
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

            <!-- Pull Settings -->
            <div class="form-section">
                <h3>{{ __('Pull Settings') }}</h3>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Pull Frequency') }}</label>
                    <div class="col-sm-9">
                        <select name="pull_frequency" class="form-control">
                            <option value="5" {{ old('pull_frequency', $settings['pull_frequency'] ?? '') == '5' ? 'selected' : '' }}>
                                {{ __('Every 5 minutes') }}
                            </option>
                            <option value="15" {{ old('pull_frequency', $settings['pull_frequency'] ?? '') == '15' ? 'selected' : '' }}>
                                {{ __('Every 15 minutes') }}
                            </option>
                            <option value="30" {{ old('pull_frequency', $settings['pull_frequency'] ?? '') == '30' ? 'selected' : '' }}>
                                {{ __('Every 30 minutes') }}
                            </option>
                            <option value="60" {{ old('pull_frequency', $settings['pull_frequency'] ?? '') == '60' ? 'selected' : '' }}>
                                {{ __('Every hour') }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-9">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="pull_attachments" value="1" 
                                    {{ old('pull_attachments', $settings['pull_attachments'] ?? '') ? 'checked' : '' }}>
                                {{ __('Pull attachments') }}
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Mapping Tabs -->
                <div class="form-section">
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#pull-mappings" aria-controls="pull-mappings" role="tab" data-toggle="tab">
                                {{ __('Pull Mappings') }}
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <!-- Pull Mappings Tab -->
                        <div role="tabpanel" class="tab-pane active" id="pull-mappings">
                            @if (!empty($settings['pullboard_id']) && count($pullboardColumns) > 0)
                                <div class="mapping-container" id="pull-mapping-container">
                                    <!-- Validation errors will be displayed here -->
                                    <form id="pull-mapping-form" class="mapping-form" action="{{ route('monday.mappings.save') }}" method="POST" data-board-type="pull" data-validate-url="{{ route('monday.mappings.validate') }}">
                                        {{ csrf_field() }}
                                        <input type="hidden" name="board_type" value="pull">
                                        <div class="mapping-header">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    {{ __('Smarticks Field') }}
                                                    <i class="glyphicon glyphicon-info-sign" data-toggle="tooltip" title="{{ __('The Smarticks field that will receive data from Monday.com') }}"></i>
                                                </div>
                                                <div class="col-md-4">
                                                    {{ __('Monday.com Column') }}
                                                    <i class="glyphicon glyphicon-info-sign" data-toggle="tooltip" title="{{ __('The Monday.com column that contains the data to be pulled') }}"></i>
                                                </div>
                                                <div class="col-md-2">
                                                    {{ __('Transform') }}
                                                    <i class="glyphicon glyphicon-info-sign" data-toggle="tooltip" title="{{ __('Optional transformation to apply to the data before storing it in Smarticks') }}"></i>
                                                </div>
                                                <div class="col-md-1">
                                                    {{ __('Required') }}
                                                </div>
                                                <div class="col-md-1">
                                                    {{ __('Actions') }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mapping-rows" id="pull-mapping-rows">
                                            @foreach($pullMappings as $index => $mapping)
                                            <div class="mapping-row" data-index="{{ $index }}">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <select name="mappings[{{ $index }}][freescout_field]" class="form-control freescout-field-select" required>
                                                            <option value="">{{ __('Select Field') }}</option>
                                                            @foreach($freescoutFields as $group => $fields)
                                                                <optgroup label="{{ $group }}">
                                                                    @foreach($fields as $field => $label)
                                                                        <option value="{{ $field }}" {{ $mapping->freescout_field == $field ? 'selected' : '' }}>{{ $label }}</option>
                                                                    @endforeach
                                                                </optgroup>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <select name="mappings[{{ $index }}][monday_column]" class="form-control monday-column-select" required>
                                                            <option value="">{{ __('Select Column') }}</option>
                                                            @foreach($pullboardColumns as $column)
                                                                <option value="{{ $column['id'] }}" data-type="{{ $column['type'] }}" {{ $mapping->monday_column == $column['id'] ? 'selected' : '' }}>
                                                                    {{ $column['title'] }} ({{ $column['type'] }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <select name="mappings[{{ $index }}][transform_rule]" class="form-control transform-rule-select">
                                                            @foreach($transformRules as $rule => $label)
                                                                <option value="{{ $rule }}" {{ $mapping->transform_rule == $rule ? 'selected' : '' }}>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-1 text-center">
                                                        <input type="checkbox" name="mappings[{{ $index }}][is_required]" value="1" {{ $mapping->is_required ? 'checked' : '' }}>
                                                    </div>
                                                    <div class="col-md-1 text-center">
                                                        <button type="button" class="btn btn-sm btn-danger remove-mapping">
                                                            <i class="glyphicon glyphicon-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="row default-value-row" style="{{ empty($mapping->default_value) ? 'display:none;' : '' }}">
                                                    <div class="col-md-4 col-md-offset-8">
                                                        <div class="input-group">
                                                            <span class="input-group-addon">{{ __('Default Value') }}</span>
                                                            <input type="text" name="mappings[{{ $index }}][default_value]" class="form-control default-value-input" value="{{ $mapping->default_value }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                            @if($pullMappings->count() == 0)
                                            <div class="mapping-row" data-index="0">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <select name="mappings[0][freescout_field]" class="form-control freescout-field-select" required>
                                                            <option value="">{{ __('Select Field') }}</option>
                                                            @foreach($freescoutFields as $group => $fields)
                                                                <optgroup label="{{ $group }}">
                                                                    @foreach($fields as $field => $label)
                                                                        <option value="{{ $field }}">{{ $label }}</option>
                                                                    @endforeach
                                                                </optgroup>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <select name="mappings[0][monday_column]" class="form-control monday-column-select" required>
                                                            <option value="">{{ __('Select Column') }}</option>
                                                            @foreach($pullboardColumns as $column)
                                                                <option value="{{ $column['id'] }}" data-type="{{ $column['type'] }}">
                                                                    {{ $column['title'] }} ({{ $column['type'] }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <select name="mappings[0][transform_rule]" class="form-control transform-rule-select">
                                                            @foreach($transformRules as $rule => $label)
                                                                <option value="{{ $rule }}">{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-1 text-center">
                                                        <input type="checkbox" name="mappings[0][is_required]" value="1">
                                                    </div>
                                                    <div class="col-md-1 text-center">
                                                        <button type="button" class="btn btn-sm btn-danger remove-mapping">
                                                            <i class="glyphicon glyphicon-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="row default-value-row" style="display:none;">
                                                    <div class="col-md-4 col-md-offset-8">
                                                        <div class="input-group">
                                                            <span class="input-group-addon">{{ __('Default Value') }}</span>
                                                            <input type="text" name="mappings[0][default_value]" class="form-control default-value-input" value="">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                        <div class="mapping-actions">
                                            <button type="button" class="btn btn-default add-mapping-btn" data-board-type="pull">
                                                <i class="glyphicon glyphicon-plus"></i> {{ __('Add Mapping') }}
                                            </button>
                                            <button type="button" class="btn btn-info suggest-mappings-btn" data-board-type="pull">
                                                <i class="glyphicon glyphicon-magic"></i> {{ __('Suggest Mappings') }}
                                            </button>
                                            <button type="button" class="btn btn-info validate-mappings-btn" data-board-type="pull">
                                                <i class="glyphicon glyphicon-check"></i> {{ __('Validate Mappings') }}
                                            </button>
                                            <button type="submit" class="btn btn-primary save-mappings-btn">
                                                <i class="glyphicon glyphicon-floppy-disk"></i> {{ __('Save Pull Mappings') }}
                                            </button>
                                            <button type="button" class="btn btn-default import-mappings-btn" data-board-type="pull">
                                                <i class="glyphicon glyphicon-upload"></i> {{ __('Import') }}
                                            </button>
                                            <input type="file" id="import-file-pull" class="import-file-input" data-board-type="pull" style="display: none;" accept=".json">
                                            <button type="button" class="btn btn-default export-mappings-btn" data-board-type="pull">
                                                <i class="glyphicon glyphicon-download-alt"></i> {{ __('Export') }}
                                            </button>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="panel panel-default">
                                                <div class="panel-heading">
                                                    <h4 class="panel-title">{{ __('Import/Export Pull Mappings') }}</h4>
                                                </div>
                                                <div class="panel-body">
                                                    <p class="help-block">{{ __('Import mappings from a JSON file or export your current mappings to a file.') }}</p>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-primary import-mappings-btn" data-board-type="pull">
                                                            <i class="glyphicon glyphicon-upload"></i> {{ __('Import Mappings') }}
                                                        </button>
                                                        <button type="button" class="btn btn-default export-mappings-btn" data-board-type="pull">
                                                            <i class="glyphicon glyphicon-download"></i> {{ __('Export Mappings') }}
                                                        </button>
                                                    </div>
                                                    <input type="file" id="import-file-pull" class="import-file-input" data-board-type="pull" style="display: none;" accept=".json,application/json">
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <p>{{ __('Please configure the Pullboard first to set up field mappings.') }}</p>
                                    <a href="{{ route('monday.settings.pullboard', ['mailbox_id' => $mailbox->id]) }}" class="btn btn-sm btn-warning">
                                        {{ __('Configure Pullboard') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group margin-top">
                <div class="col-sm-offset-3 col-sm-9">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Save Pullboard Settings') }}
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
    window.onload = function () {
        // Test board connection
        $('.test-board-connection').on('click', function() {
            var $button = $(this);
            var $status = $('.connection-status');
            var apiToken = $('input[name="api_token"]').val();
            var boardId = $('select[name="pullboard_id"]').val();
            
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
