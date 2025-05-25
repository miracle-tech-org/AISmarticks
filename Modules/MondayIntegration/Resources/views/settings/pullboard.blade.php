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
                    <div class="col-sm-4">
                        <input type="text" value="{{$settings['pullboard_id']}}" class="form-control" id="selected_id"/>
                        <p class="help-block">
                            {{ __('Select the Monday.com board to pull data from.') }}
                        </p>
                    </div>
                    <div class="col-sm-4">
                        <select name="pullboard_id" id="pullboard_id" class="form-control" required>
                            <option value="">{{ __('Select Board') }}</option>
                            @foreach($boards['boards'] as $board)
                                <option value="{{ $board['id'] }}" 
                                    {{ (old('pullboard_id', $settings['pullboard_id'] ?? '') == $board['id']) ? 'selected' : '' }}>
                                        {{ $board['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-1">
                        <button class="btn btn-primary" type="button" id="btnApply">Apply</button>
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
                            <option value="0" {{ old('sync_frequency', $settings['sync_frequency'] ?? '') == '0' ? 'selected' : '' }}>
                                {{ __('Disable Pull Frequency') }}
                            </option>
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
                                            {{ __('Search KEY') }}
                                            <i class="glyphicon glyphicon-info-sign" data-toggle="tooltip" title="{{ __('Optional transformation to apply to the data before storing it in Smarticks') }}"></i>
                                        </div>
                                        <div class="col-md-1">
                                            {{ __('Overwrite') }}
                                        </div>
                                        <div class="col-md-1">
                                            {{ __('Actions') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="mapping-rows" id="pull-mapping-rows">
                                    @foreach($pullMappings as $index => $mapping)
                                        <div class="mapping-row" data-index="{{ $index }}" style="margin-top: 2%">
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
                                                                {{ $column['title'] }} ({{ $column['title'] == 'Status' ? 'number' : 'text' }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="radio" name="search_key" value="{{ $index }}" {{ $mapping->search_key ? 'checked' : '' }}>
                                                </div>
                                                <div class="col-md-1">
                                                    <input type="checkbox" name="mappings[{{ $index }}][is_overwrite]" value="1" {{ $mapping->is_overwrite ? 'checked' : '' }}>
                                                </div>
                                                <div class="col-md-1 text-center">
                                                    <button type="button" class="btn btn-sm btn-danger remove-mapping">
                                                        <i class="glyphicon glyphicon-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mapping-actions" style="margin-top: 2%; margin-bottom: 2%">
                                    <button type="button" class="btn btn-default add-mapping-btn" data-board-type="pull">
                                        <i class="glyphicon glyphicon-plus"></i> {{ __('Add Mapping') }}
                                    </button>
                                    <button type="submit" class="btn btn-primary save-mappings-btn">
                                        <i class="glyphicon glyphicon-floppy-disk"></i> {{ __('Save Pull Mappings') }}
                                    </button>

                                    <button type="button" class="btn btn-primary sync-mappings-btn">
                                        <i class="glyphicon glyphicon-refresh"></i> {{ __('Sync now') }}
                                    </button>
                                    <input type="file" id="import-file-pull" class="import-file-input" data-board-type="pull" style="display: none;" accept=".json">
                                </div>
                                <textarea id="sync-status" cols="30" rows="10" class="form-control"></textarea>
                                <h4>Last Sync Logs</h4>
                                @if(!empty($logs))
                                    <ul class="list-disc pl-5 text-sm text-gray-700">
                                        @foreach($logs as $log)
                                            <li>{{ $log }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-gray-500">No sync logs found.</p>
                                @endif

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
    <div class="mapping-row" data-index="__INDEX__">
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
            <div class="col-md-2">
                <input type="radio" name="search_key" value="__INDEX__" />
            </div>
            <div class="col-md-1">
                <input type="checkbox" name="mappings[0][is_overwrite]" value="1" />
            </div>
            <div class="col-md-1 text-right">
                <button type="button" class="btn btn-sm btn-danger remove-mapping">
                    <i class="glyphicon glyphicon-trash"></i>
                </button>
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
                isClick: 'yes'
            }
            $.post(url, data, (res) => {
                if(res['items_page'] == undefined) {
                    $("#sync-status").append(`No item to sync`);
                } else {
                    $("#sync-status").append(`Connected to board: "{{$pullboardName}}"\n`);
                    $("#sync-status").append(`Found ${res['items_page']['items'].length} row to sync.\n`);
                    
                    res['items_page']['items'].forEach(item => {
                        $("#sync-status").append(`Customer name: ${item.name} Pulled\n`);
                    });
    
                    $("#sync-status").append(`${res['items_page']['items'].length} rows has been successfully synced\n0 rows ware skipped.\n`);
                }
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

            const updatedTemplate = template
                .replace(/\[0\]/g, '[' + nextIndex + ']')
                .replace(/__INDEX__/g, nextIndex); // For radio value
            
            // Replace the index placeholder with the actual index
            // const row = $(template.replace(/\[0\]/g, '[' + nextIndex + ']'));
            const row = $(updatedTemplate);
            
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

        $("#btnApply").on('click', function() {
            var selected_id = $("#selected_id").val();

            var exists = $("#pullboard_id").find(`option[value="${selected_id}"]`).length > 0;
            if(!exists) {
                alert("Invalid Monday board ID provided. Please verify the ID and try again.")
                return;
            }

            $("#pullboard_id").val(selected_id);
        })

        $("#pullboard_id").on('change', function() {
            var selected_id = $("#pullboard_id").val();
            $("#selected_id").val(selected_id);
        })
    };
</script>
