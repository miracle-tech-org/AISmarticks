@extends('layouts.app')

@section('title', __('Field Mapping').' - '.$mailbox->name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Monday.com Field Mapping') }}
    </div>

    <div class="row-container">
        <div class="col-md-12">
            <div class="mapping-container">
                <!-- Available Fields -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">{{ __('Available Fields') }}</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4>{{ __('FreeScout Fields') }}</h4>
                                <div class="field-list" id="freescout-fields">
                                    @foreach ($freescoutFields as $group => $fields)
                                        <div class="field-group">
                                            <h5>{{ $group }}</h5>
                                            @foreach ($fields as $field => $label)
                                                <div class="field-item" draggable="true" data-field="{{ $field }}"
                                                    data-source="freescout">
                                                    <i class="glyphicon glyphicon-menu-hamburger"></i>
                                                    {{ $label }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h4>{{ __('Monday.com Columns') }}</h4>
                                <div class="field-list" id="monday-fields">
                                    @foreach ($mondayColumns['boards'] as $mondayItem)
                                        <div class="field-group">
                                            <h5>{{ $mondayItem['name'] }}</h5>
                                            @foreach ($mondayItem['columns'] as $column)
                                                <div class="field-item" draggable="true" data-field="{{ $column['id'] }}"
                                                    data-source="monday">
                                                    <i class="glyphicon glyphicon-menu-hamburger"></i>
                                                    {{ $column['title'] }}
                                                    <span class="field-type">{{ $column['type'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Field Mappings -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">{{ __('Field Mappings') }}</h3>
                    </div>
                    <div class="panel-body">
                        <form id="mapping-form" action="{{ route('monday.mappings.save') }}" method="POST">
                            <div id="mapping-list">
                                @foreach ($mappings as $mapping)
                                    <div class="mapping-item" data-id="{{ $mapping->id }}">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <div class="field-box freescout-field">
                                                    {{ $mapping->smarticks_field_label }}
                                                    <input type="hidden"
                                                        name="mappings[{{ $loop->index }}][smarticks_field]"
                                                        value="{{ $mapping->smarticks_field }}">
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="field-box monday-field">
                                                    {{ $mapping->monday_column_label }}
                                                    <input type="hidden"
                                                        name="mappings[{{ $loop->index }}][monday_column]"
                                                        value="{{ $mapping->monday_column }}">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="mapping-actions">
                                                    <button type="button" class="btn btn-sm btn-default edit-mapping">
                                                        <i class="glyphicon glyphicon-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger remove-mapping">
                                                        <i class="glyphicon glyphicon-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mapping-settings collapse">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>{{ __('Transform Rule') }}</label>
                                                        <select name="mappings[{{ $loop->index }}][transform_rule]"
                                                            class="form-control">
                                                            <option value="">{{ __('None') }}</option>
                                                            @foreach ($transformRules as $rule => $label)
                                                                <option value="{{ $rule }}"
                                                                    {{ $mapping->transform_rule == $rule ? 'selected' : '' }}>
                                                                    {{ $label }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>{{ __('Default Value') }}</label>
                                                        <input type="text"
                                                            name="mappings[{{ $loop->index }}][default_value]"
                                                            class="form-control" value="{{ $mapping->default_value }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="checkbox">
                                                        <label>
                                                            <input type="checkbox"
                                                                name="mappings[{{ $loop->index }}][is_required]"
                                                                value="1"
                                                                {{ $mapping->is_required ? 'checked' : '' }}>
                                                            {{ __('Required Field') }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="form-group margin-top">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Save Mappings') }}
                                </button>
                                <a href="{{ route('monday.dashboard', ['mailbox_id' => $mailbox->id]) }}"
                                    class="btn btn-link">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mapping Template -->
    <script type="text/template" id="mapping-template">
        <div class="mapping-item" data-id="">
            <div class="row">
                <div class="col-md-5">
                    <div class="field-box freescout-field">
                        {freescout_label}
                        <input type="hidden" name="mappings[{index}][smarticks_field]" value="{freescout_field}">
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="field-box monday-field">
                        {monday_label}
                        <input type="hidden" name="mappings[{index}][monday_column]" value="{monday_column}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mapping-actions">
                        <button type="button" class="btn btn-sm btn-default edit-mapping">
                            <i class="glyphicon glyphicon-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger remove-mapping">
                            <i class="glyphicon glyphicon-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="mapping-settings collapse">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('Transform Rule') }}</label>
                            <select name="mappings[{index}][transform_rule]" class="form-control">
                                <option value="">{{ __('None') }}</option>
                                @foreach($transformRules as $rule => $label)
                                    <option value="{{ $rule }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('Default Value') }}</label>
                            <input type="text" name="mappings[{index}][default_value]" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="mappings[{index}][is_required]" value="1">
                                {{ __('Required Field') }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </script>
@endsection

@push('css')
    <style>
        .mapping-container {
            margin-bottom: 30px;
        }

        .field-list {
            min-height: 200px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
        }

        .field-group {
            margin-bottom: 15px;
        }

        .field-group h5 {
            color: #666;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .field-item {
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 5px 10px;
            margin-bottom: 5px;
            cursor: move;
        }

        .field-item:hover {
            background: #f0f0f0;
        }

        .field-item i {
            color: #999;
            margin-right: 5px;
        }

        .field-type {
            float: right;
            color: #999;
            font-size: 12px;
        }

        .mapping-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
        }

        .field-box {
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 8px 12px;
            min-height: 36px;
        }

        .mapping-actions {
            text-align: right;
        }

        .mapping-settings {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .margin-top {
            margin-top: 20px;
        }

        .field-box.drag-over {
            background: #e8f0fe;
            border: 2px dashed #4a89dc;
        }
    </style>
@endpush

<script>
    $(document).ready(function() {
        // Initialize drag and drop
        initializeDragAndDrop();

        // Handle mapping actions
        $('#mapping-list').on('click', '.edit-mapping', function() {
            $(this).closest('.mapping-item').find('.mapping-settings').collapse('toggle');
        });

        $('#mapping-list').on('click', '.remove-mapping', function() {
            $(this).closest('.mapping-item').remove();
            updateMappingIndexes();
        });

        // Handle form submission
        $('#mapping-form').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    showFloatingAlert('success', response.message);
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                },
                error: function(xhr) {
                    showFloatingAlert('danger', xhr.responseJSON.message ||
                        'Failed to save mappings');
                }
            });
        });

        function initializeDragAndDrop() {
            // Make items draggable
            $('.field-item').on('dragstart', function(e) {
                e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify({
                    field: $(this).data('field'),
                    source: $(this).data('source'),
                    label: $(this).text().trim()
                }));
            });

            // Handle drag over
            $('.field-box').on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });

            // Handle drag leave
            $('.field-box').on('dragleave', function(e) {
                $(this).removeClass('drag-over');
            });

            // Handle drop
            $('.field-box').on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');

                var data = JSON.parse(e.originalEvent.dataTransfer.getData('text/plain'));
                var $mappingItem = $(this).closest('.mapping-item');

                if ($(this).hasClass('freescout-field')) {
                    if (data.source === 'freescout') {
                        $(this).html(data.label);
                        $mappingItem.find('input[name$="[smarticks_field]"]').val(data.field);
                    }
                } else if ($(this).hasClass('monday-field')) {
                    if (data.source === 'monday') {
                        $(this).html(data.label);
                        $mappingItem.find('input[name$="[monday_column]"]').val(data.field);
                    }
                }
            });
        }

        function updateMappingIndexes() {
            $('#mapping-list .mapping-item').each(function(index) {
                $(this).find('input, select').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                    }
                });
            });
        }

        function addNewMapping(freescoutField, mondayColumn) {
            var template = $('#mapping-template').html();
            var index = $('#mapping-list .mapping-item').length;

            template = template.replace(/{index}/g, index)
                .replace(/{freescout_field}/g, freescoutField.field)
                .replace(/{freescout_label}/g, freescoutField.label)
                .replace(/{monday_column}/g, mondayColumn.field)
                .replace(/{monday_label}/g, mondayColumn.label);

            $('#mapping-list').append(template);
            initializeDragAndDrop();
        }
    });
</script>
