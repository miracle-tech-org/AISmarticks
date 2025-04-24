@extends('layouts.app')

@section('title', __('Monday.com Field Mappings'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
<div class="section-heading">
    {{ __('Monday.com Field Mappings') }}
</div>

@include('mondayintegration::settings.partials.navigation')

<!-- Alert Container for Field Mapping Assistant -->
<div class="alert-container"></div>

<div class="row-container">
    <div class="col-md-12">
        <div class="alert alert-info">
            <p>{{ __('Field mappings define how data is synchronized between Smarticks and Monday.com.') }}</p>
            <ul>
                <li><strong>{{ __('Pull Mappings:') }}</strong> {{ __('Define how data from Monday.com is mapped to Smarticks fields.') }} <i class="glyphicon glyphicon-info-sign" data-toggle="tooltip" title="{{ __('These mappings control how Monday.com data appears in Smarticks.') }}"></i></li>
                <li><strong>{{ __('Push Mappings:') }}</strong> {{ __('Define how Smarticks data is mapped to Monday.com columns.') }} <i class="glyphicon glyphicon-info-sign" data-toggle="tooltip" title="{{ __('These mappings control how Smarticks data is sent to Monday.com.') }}"></i></li>
            </ul>
        </div>

        <!-- Board Status -->
        <div class="form-section">
            <h3>{{ __('Board Configuration Status') }}</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="panel {{ !empty($settings['pullboard_id']) ? 'panel-success' : 'panel-warning' }}">
                        <div class="panel-heading">
                            <h4 class="panel-title">{{ __('Pullboard') }}</h4>
                        </div>
                        <div class="panel-body">
                            @if (!empty($settings['pullboard_id']))
                                <p><strong>{{ __('Board ID:') }}</strong> {{ $settings['pullboard_id'] }}</p>
                                @foreach($boards['boards'] as $board)
                                    @if ((old('pullboard_id', $settings['pullboard_id'] ?? '') == $board['id']))
                                        <p><strong>{{ __('Board Name:') }}</strong> {{ $board['name'] }}</p>
                                    @endif
                                @endforeach

                                <p><strong>{{ __('Columns:') }}</strong> {{ count($pullboardColumns) }}</p>
                                <p><strong>{{ __('Mappings:') }}</strong> {{ $pullMappings->count() }}</p>
                                <a href="{{ route('monday.settings.pullboard', ['mailbox_id' => $mailbox->id]) }}" class="btn btn-sm btn-default">
                                    {{ __('Configure Pullboard') }}
                                </a>
                            @else
                                <p>{{ __('Pullboard not configured.') }}</p>
                                <a href="{{ route('monday.settings.pullboard', ['mailbox_id' => $mailbox->id]) }}" class="btn btn-sm btn-warning">
                                    {{ __('Configure Pullboard') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel {{ !empty($settings['pushboard_id']) ? 'panel-success' : 'panel-warning' }}">
                        <div class="panel-heading">
                            <h4 class="panel-title">{{ __('Pushboard') }}</h4>
                        </div>
                        <div class="panel-body">
                            @if (!empty($settings['pushboard_id']))
                                <p><strong>{{ __('Board ID:') }}</strong> {{ $settings['pushboard_id'] }}</p>
                                @foreach($boards['boards'] as $board)
                                    @if ((old('pushboard_id', $settings['pushboard_id'] ?? '') == $board['id']))
                                        <p><strong>{{ __('Board Name:') }}</strong> {{ $board['name'] }}</p>
                                    @endif
                                @endforeach
                                <p><strong>{{ __('Columns:') }}</strong> {{ count($pushboardColumns) }}</p>
                                <p><strong>{{ __('Mappings:') }}</strong> {{ $pushMappings->count() }}</p>
                                <a href="{{ route('monday.settings.pushboard', ['mailbox_id' => $mailbox->id]) }}" class="btn btn-sm btn-default">
                                    {{ __('Configure Pushboard') }}
                                </a>
                            @else
                                <p>{{ __('Pushboard not configured.') }}</p>
                                <a href="{{ route('monday.settings.pushboard', ['mailbox_id' => $mailbox->id]) }}" class="btn btn-sm btn-warning">
                                    {{ __('Configure Pushboard') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import/Export Tools -->
        <div class="form-section">
            <h3>
                {{ __('Import/Export Tools') }}
                <i class="glyphicon glyphicon-info-sign" data-toggle="tooltip" title="{{ __('Use these tools to backup your mappings or transfer them between environments.') }}"></i>
            </h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">{{ __('Export Mappings') }}</h4>
                        </div>
                        <div class="panel-body">
                            <p>{{ __('Export your field mappings as a JSON file for backup or transfer.') }}</p>
                            <form action="{{ route('monday.mappings.export') }}" method="GET" class="form-horizontal">
                                <div class="form-group">
                                    <label class="control-label col-sm-4">{{ __('Board Type:') }}</label>
                                    <div class="col-sm-8">
                                        <select name="board_type" class="form-control">
                                            <option value="all">{{ __('All Mappings') }}</option>
                                            <option value="pull">{{ __('Pull Mappings Only') }}</option>
                                            <option value="push">{{ __('Push Mappings Only') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-offset-4 col-sm-8">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="glyphicon glyphicon-download-alt"></i> {{ __('Export') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">{{ __('Import Mappings') }}</h4>
                        </div>
                        <div class="panel-body">
                            <p>{{ __('Import field mappings from a previously exported JSON file.') }}</p>
                            <form id="import-form" action="{{ route('monday.mappings.import') }}" method="POST" enctype="multipart/form-data" class="form-horizontal">
                                {{ csrf_field() }}
                                <div class="form-group">
                                    <label class="control-label col-sm-4">{{ __('Mapping File:') }}</label>
                                    <div class="col-sm-8">
                                        <input type="file" name="mapping_file" class="form-control" accept=".json" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-offset-4 col-sm-8">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="overwrite" value="1"> 
                                                {{ __('Overwrite existing mappings') }}
                                                <i class="glyphicon glyphicon-info-sign" data-toggle="tooltip" title="{{ __('If checked, all existing mappings of the same board type will be replaced. If unchecked, only new mappings will be added.') }}"></i>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-offset-4 col-sm-8">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="glyphicon glyphicon-upload"></i> {{ __('Import') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Templates for adding new mappings -->
<script type="text/template" id="pull-mapping-template">
    <div class="mapping-row" data-index="0">
        <div class="row">
            <div class="col-md-4">
                <select name="mappings[0][freescout_field]" class="form-control freescout-field-select" required>
                    <option value="">{{ __('Select Field') }}</option>
                    @foreach($freescoutFields as $category => $fields)
                    <optgroup label="{{ $category }}">
                        @foreach($fields as $field => $title)
                        <option value="{{ $field }}">{{ $title }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select name="mappings[0][monday_column]" class="form-control monday-column-select" required>
                    <option value="">{{ __('Select Column') }}</option>
                    @foreach($pullboardColumns as $column)
                    <option value="{{ $column['id'] }}" data-type="{{ $column['type'] }}">{{ $column['title'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="mappings[0][transform_rule]" class="form-control transform-rule-select">
                    <option value="">{{ __('No Transform') }}</option>
                    <option value="date_format">{{ __('Date Format') }}</option>
                    <option value="phone_format">{{ __('Phone Format') }}</option>
                    <option value="extract_email">{{ __('Extract Email') }}</option>
                    <option value="extract_phone">{{ __('Extract Phone') }}</option>
                    <option value="boolean">{{ __('Boolean') }}</option>
                    <option value="default_value">{{ __('Default Value') }}</option>
                    <option value="fallback_value">{{ __('Fallback Value') }}</option>
                </select>
                <div class="default-value-row">
                    <input type="text" name="mappings[0][default_value]" class="form-control" placeholder="{{ __('Default/Fallback Value') }}">
                </div>
            </div>
            <div class="col-md-1 text-right">
                <div class="compatibility-indicator"></div>
                <span class="drag-handle"><i class="glyphicon glyphicon-move"></i></span>
                <span class="remove-mapping"><i class="glyphicon glyphicon-trash"></i></span>
            </div>
        </div>
    </div>
</script>

<script type="text/template" id="push-mapping-template">
    <div class="mapping-row" data-index="0">
        <div class="row">
            <div class="col-md-4">
                <select name="mappings[0][freescout_field]" class="form-control freescout-field-select" required>
                    <option value="">{{ __('Select Field') }}</option>
                    @foreach($freescoutFields as $category => $fields)
                    <optgroup label="{{ $category }}">
                        @foreach($fields as $field => $title)
                        <option value="{{ $field }}">{{ $title }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select name="mappings[0][monday_column]" class="form-control monday-column-select" required>
                    <option value="">{{ __('Select Column') }}</option>
                    @foreach($pushboardColumns as $column)
                    <option value="{{ $column['id'] }}" data-type="{{ $column['type'] }}">{{ $column['title'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="mappings[0][transform_rule]" class="form-control transform-rule-select">
                    <option value="">{{ __('No Transform') }}</option>
                    <option value="date_format">{{ __('Date Format') }}</option>
                    <option value="phone_format">{{ __('Phone Format') }}</option>
                    <option value="extract_email">{{ __('Extract Email') }}</option>
                    <option value="extract_phone">{{ __('Extract Phone') }}</option>
                    <option value="boolean">{{ __('Boolean') }}</option>
                    <option value="default_value">{{ __('Default Value') }}</option>
                    <option value="fallback_value">{{ __('Fallback Value') }}</option>
                </select>
                <div class="default-value-row">
                    <input type="text" name="mappings[0][default_value]" class="form-control" placeholder="{{ __('Default/Fallback Value') }}">
                </div>
            </div>
            <div class="col-md-1 text-right">
                <div class="compatibility-indicator"></div>
                <span class="drag-handle"><i class="glyphicon glyphicon-move"></i></span>
                <span class="remove-mapping"><i class="glyphicon glyphicon-trash"></i></span>
            </div>
        </div>
    </div>
</script>

@push('css')
<style>
    .form-section {
        margin-bottom: 30px;
    }
    .mapping-container {
        margin-bottom: 20px;
    }
    .mapping-header {
        background-color: #f8f8f8;
        padding: 10px;
        border: 1px solid #ddd;
        border-bottom: none;
        border-radius: 4px 4px 0 0;
        font-weight: bold;
    }
    .mapping-rows {
        border: 1px solid #ddd;
        border-radius: 0 0 4px 4px;
        padding: 10px;
        background-color: #fff;
    }
    .mapping-row {
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px dashed #eee;
    }
    .mapping-row:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    .default-value-row {
        margin-top: 10px;
    }
    .mapping-actions {
        margin-top: 15px;
    }
    .has-error .form-control {
        border-color: #a94442;
        box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
    }
    .validation-error, .validation-success {
        margin-bottom: 15px;
    }
    .drag-handle {
        cursor: move;
        margin-top: 5px;
    }
    /* Tooltip styles */
    .tooltip-inner {
        max-width: 250px;
        padding: 8px 12px;
        background-color: #333;
        border-radius: 4px;
        font-size: 12px;
    }
    /* Import/Export panel styles */
    .panel-heading {
        font-weight: bold;
    }
    .panel-body {
        min-height: 150px;
    }
    /* Field Mapping Assistant Styles */
    .mapping-row {
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 10px;
        padding: 10px;
        position: relative;
    }
    
    .mapping-row .drag-handle {
        cursor: move;
        color: #999;
    }
    
    .mapping-row .remove-mapping {
        color: #d9534f;
    }
    
    .default-value-row {
        margin-top: 10px;
        display: none;
    }
    
    .mapping-actions {
        margin-top: 15px;
        margin-bottom: 20px;
    }
    
    .mapping-actions button {
        margin-right: 5px;
        margin-bottom: 5px;
    }
    
    .compatibility-warning {
        margin-left: 10px;
        cursor: help;
    }
    
    .compatibility-warning i {
        font-size: 16px;
    }
    
    .suggestions-container {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.7);
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .loading-spinner {
        background-color: white;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        text-align: center;
    }
    
    .spinning {
        animation: spin 1s infinite linear;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .alert-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        width: 350px;
    }
    
    .compatibility-indicator {
        text-align: center;
    }
</style>
@endpush

@push('stylesheets')
<style>
    .mapping-row {
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 10px;
        padding: 10px;
        position: relative;
    }
    
    .mapping-row .drag-handle {
        cursor: move;
        color: #999;
    }
    
    .mapping-row .remove-mapping {
        color: #d9534f;
    }
    
    .default-value-row {
        margin-top: 10px;
        display: none;
    }
    
    .mapping-actions {
        margin-top: 15px;
        margin-bottom: 20px;
    }
    
    .mapping-actions button {
        margin-right: 5px;
        margin-bottom: 5px;
    }
    
    .compatibility-warning {
        margin-left: 10px;
        cursor: help;
    }
    
    .compatibility-warning i {
        font-size: 16px;
    }
    
    /* Suggestions modal styling */
    #suggestions-table th:first-child,
    #suggestions-table td:first-child {
        width: 40px;
        text-align: center;
    }
    
    #suggestions-table .label {
        display: inline-block;
        min-width: 60px;
        text-align: center;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('modules/mondayintegration/js/field-mapping.js') }}"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script>
    // Global configuration for Monday.com integration
    var mondayIntegration = {
        routes: {
            save_mappings: "{{ route('monday.mappings.save', ['mailbox_id' => $mailbox->id]) }}",
            validate_mappings: "{{ route('monday.mappings.validate', ['mailbox_id' => $mailbox->id]) }}",
            import_mappings: "{{ route('monday.mappings.import', ['mailbox_id' => $mailbox->id]) }}",
            export_mappings: "{{ route('monday.mappings.export', ['mailbox_id' => $mailbox->id]) }}",
            suggest_mappings: "{{ route('monday.mappings.suggest', ['mailbox_id' => $mailbox->id]) }}",
            validate_compatibility: "{{ route('monday.mappings.validate-compatibility', ['mailbox_id' => $mailbox->id]) }}",
            get_board_columns: "{{ route('monday.mappings.board.columns', ['mailbox_id' => $mailbox->id, 'boardId' => 'boardId']) }}",
            get_fields: "{{ route('monday.mappings.fields', ['mailbox_id' => $mailbox->id]) }}"
        },
        i18n: {
            saving: '{{ __('Saving...') }}',
            validating: '{{ __('Validating...') }}',
            generating: '{{ __('Generating suggestions...') }}',
            error_saving: '{{ __('Error saving mappings') }}',
            error_validating: '{{ __('Error validating mappings') }}',
            error_suggesting: '{{ __('Error generating suggestions') }}',
            validation_failed: '{{ __('Validation failed') }}',
            validation_errors: '{{ __('Please fix the validation errors') }}',
            no_mappings: '{{ __('No mappings defined') }}',
            duplicate_field: '{{ __('Duplicate Smarticks field') }}',
            duplicate_column: '{{ __('Duplicate Monday.com column') }}',
            field_required: '{{ __('Field is required') }}',
            column_required: '{{ __('Column is required') }}',
            default_value_required: '{{ __('Default value is required') }}',
            invalid_file_type: '{{ __('Invalid file type. Please select a JSON file.') }}',
            invalid_file_format: '{{ __('Invalid file format. The file does not contain valid mapping data.') }}',
            board_type_mismatch: '{{ __('Board type mismatch. The imported mappings are for a different board type.') }}',
            no_mappings_found: '{{ __('No mappings found in the imported file.') }}',
            import_success: '{{ __('Mappings imported successfully.') }}',
            invalid_json: '{{ __('Invalid JSON file') }}',
            suggested_mappings: '{{ __('Suggested Field Mappings') }}',
            suggestions_help: '{{ __('Select the mappings you want to apply. These suggestions are based on field name and type similarity.') }}',
            monday_column: '{{ __('Monday.com Column') }}',
            freescout_field: '{{ __('Smarticks Field') }}',
            transform_rule: '{{ __('Transform Rule') }}',
            confidence: '{{ __('Confidence') }}',
            very_high: '{{ __('Very High') }}',
            high: '{{ __('High') }}',
            medium: '{{ __('Medium') }}',
            low: '{{ __('Low') }}',
            very_low: '{{ __('Very Low') }}',
            none: '{{ __('None') }}',
            cancel: '{{ __('Cancel') }}',
            apply_selected: '{{ __('Apply Selected') }}',
            no_suggestions: '{{ __('No mapping suggestions available.') }}',
            no_suggestions_selected: '{{ __('Please select at least one mapping suggestion.') }}',
            clear_existing_mappings: '{{ __('Do you want to clear existing mappings before applying the suggestions?') }}',
            suggestions_applied: '{{ __('Mapping suggestions applied successfully.') }}'
        }
    };
</script>
<script>
    $(document).ready(function() {
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
@endpush

@endsection