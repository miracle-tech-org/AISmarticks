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
            </div>
        </form>

        <!-- Mapping Tabs -->
        <div class="form-section">
            <ul class="nav nav-tabs" role="tablist">
            </ul>
            <div class="tab-content">
                <!-- Pull Mappings Tab -->
                <div role="tabpanel" class="tab-pane active" id="pull-mappings">
                    @if (!empty($settings['pullboard_id']) && count($pullboardColumns) > 0)
                        <div class="mapping-container" id="pull-mapping-container">
                            <!-- Validation errors will be displayed here -->
                            <form id="pull-mapping-form" class="mapping-form" action="{{ route('monday.mappings.save') }}" method="POST" data-board-type="pull">
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
                                    <br/>
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
                                <br />
                                <div class="mapping-actions">
                                    <button type="button" class="btn btn-default add-mapping-btn" data-board-type="pull">
                                        <i class="glyphicon glyphicon-plus"></i> {{ __('Add Mapping') }}
                                    </button>
                                    {{-- <button type="button" class="btn btn-info suggest-mappings-btn" data-board-type="pull">
                                        <i class="glyphicon glyphicon-magic"></i> Suggest Mappings
                                    </button>
                                    <button type="button" class="btn btn-info validate-mappings-btn" data-board-type="pull">
                                        <i class="glyphicon glyphicon-check"></i> Validate Mappings
                                    </button> --}}
                                    <button type="submit" class="btn btn-primary save-mappings-btn">
                                        <i class="glyphicon glyphicon-floppy-disk"></i> {{ __('Save Pull Mappings') }}
                                    </button>

                                    <button type="button" class="btn btn-primary sync-mappings-btn">
                                        <i class="glyphicon glyphicon-refresh"></i> {{ __('Sync now') }}
                                    </button>
                                    {{-- <button type="button" class="btn btn-default import-mappings-btn" data-board-type="pull">
                                        <i class="glyphicon glyphicon-upload"></i> Import
                                    </button> --}}
                                    <input type="file" id="import-file-pull" class="import-file-input" data-board-type="pull" style="display: none;" accept=".json">
                                    {{-- <button type="button" class="btn btn-default export-mappings-btn" data-board-type="pull">
                                        <i class="glyphicon glyphicon-download-alt"></i> Export
                                    </button> --}}
                                </div>
                                <br/>
                                <textarea id="sync-status" cols="30" rows="10" class="form-control"></textarea>
                                {{-- Here is Import Field --}}
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
</div>

<script type="text/template" id="pull-mapping-template">
    <br/>
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
@php
    // Initialize $pullboardName as null or an empty string
    $pullboardName = "";

    // Get the selected pullboard_id from old input or settings
    $selectedPullboardId = old('pullboard_id', $settings['pullboard_id'] ?? null);

    // Loop through boards to find the name of the selected board
    foreach ($boards['boards'] as $board) {
        if ($board['id'] == $selectedPullboardId) {
            $pullboardName = $board['name'];
            break; // Exit the loop once the match is found
        }
    }
@endphp

<script>
    window.onload = function () {
        $(document).on('click', '.remove-mapping', function() {
            const form = $(this).closest('form');
            const boardType = form.data('board-type');
            
            $(this).closest('.mapping-row').remove();
            updateMappingIndices(boardType);
        });

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

        $('.add-mapping-btn').on('click', function() {
            const boardType = $(this).data('board-type');
            addNewMappingRow(boardType);
        });

        $(".sync-mappings-btn").on('click', function() {
            var url = "{{route('monday.sync-now')}}";
            const now = new Date();

            // Extract hours, minutes, and seconds
            const hours = String(now.getHours()).padStart(2, '0'); // Ensure two digits
            const minutes = String(now.getMinutes()).padStart(2, '0'); // Ensure two digits
            const seconds = String(now.getSeconds()).padStart(2, '0'); // Ensure two digits

            // Optionally, extract milliseconds if needed
            const milliseconds = String(now.getMilliseconds()).padStart(3, '0'); // Ensure three digits

            // Format the time string
            const currentTime = `${hours}:${minutes}:${seconds}.${milliseconds}`;
            $("#sync-status").html(`Sync started on: ${currentTime}\n`);
            var data = {
                board_id: "{{old('pullboard_id', $settings['pullboard_id'] ?? '')}}"
            }
            $.post(url, data, (res) => {
                $("#sync-status").append(`Connected to board: "{{$pullboardName}}"\n`);
                $("#sync-status").append(`Found ${res['items_page']['items'].length} row to sync.\n`);
                
                res['items_page']['items'].forEach(item => {
                    $("#sync-status").append(`ID: ${item.id} Name: ${item.name} URL: ${item.url} Columns: ${JSON.stringify(item.column_values)}\n`);
                });

                $("#sync-status").append(`${res['items_page']['items'].length} rows has been successfully synced\n0 rows ware skipped.\n`);
            })
        })

        function addNewMappingRow(boardType) {
            const row = addMappingRow(boardType);
            
            // Scroll to the new row
            $('html, body').animate({
                scrollTop: row.offset().top - 100
            }, 500);
        }

        function addMappingRow(boardType, mappingData = null) {
            // Get the template
            const template = $('#' + boardType + '-mapping-template').html();
            const mappingRows = $('#' + boardType + '-mapping-rows');
            
            // Get the next index
            const nextIndex = mappingRows.children().length;
            
            // Replace the index placeholder with the actual index
            const row = $(template.replace(/\[0\]/g, '[' + nextIndex + ']'));
            
            // Set values if mapping data is provided
            if (mappingData) {
                row.find('.freescout-field-select').val(mappingData.freescout_field);
                row.find('.monday-column-select').val(mappingData.monday_column);
                row.find('.transform-rule-select').val(mappingData.transform_rule || '');
                row.find('.default-value-input').val(mappingData.default_value || '');
                row.find('.required-checkbox').prop('checked', mappingData.is_required || false);
                
                // Show/hide default value row based on transform rule
                const transformRule = mappingData.transform_rule || '';
                if (transformRule === 'default_value' || transformRule === 'fallback_value') {
                    row.find('.default-value-row').show();
                } else {
                    row.find('.default-value-row').hide();
                }
            }
            
            // Add the row to the container
            mappingRows.append(row);
            
            // Initialize tooltips
            row.find('[data-toggle="tooltip"]').tooltip();
            
            // Trigger change event to validate compatibility
            row.find('.monday-column-select, .freescout-field-select').trigger('change');
            
            return row;
        }

        function updateMappingIndices(boardType) {
            const mappingRows = $('#' + boardType + '-mapping-rows').children();
            
            mappingRows.each(function(index) {
                const row = $(this);
                
                // Update the index data attribute
                row.attr('data-index', index);
                
                // Update the name attributes of all form elements
                row.find('select, input').each(function() {
                    const element = $(this);
                    const name = element.attr('name');
                    
                    if (name) {
                        const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                        element.attr('name', newName);
                    }
                });
            });
        }
    };
</script>
