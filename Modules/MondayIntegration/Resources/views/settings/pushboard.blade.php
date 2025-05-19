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
            <form action="{{ route('monday.settings.save', ['mailbox_id' => $mailbox->id]) }}" method="POST"
                class="form-horizontal">
                <input type="hidden" name="form_origin" value="pushboard">

                <!-- API Configuration -->
                <div class="form-section">
                    <h3>{{ __('Pushboard Configuration') }}</h3>
                    <p class="help-block">
                        {{ __('Configure the board to which data will be pushed from Smarticks.') }}
                    </p>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{ __('Monday Name') }}</label>
                        <div class="col-sm-9">
                            <input type="hidden" name="api_token" class="form-control"
                                value="{{ old('api_token', $settings['api_token'] ?? '') }}" required>
                            <input type="text" class="form-control" value="{{ $mondayName }}">
                            <p class="help-block">
                                {{ __('Your Monday.com API token. Get it from your Monday.com account settings.') }}
                            </p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{ __('Pushboard ID') }}</label>
                        <div class="col-sm-4">
                            <input type="text" value="{{ $settings['pushboard_id'] }}" class="form-control"
                                id="selected_id" />
                            <p class="help-block">
                                {{ __('Select the Monday.com board to push data to.') }}
                            </p>
                        </div>
                        <div class="col-sm-4">
                            <select name="pushboard_id" id="pushboard_id" class="form-control" required>
                                <option value="">{{ __('Select Board') }}</option>
                                @foreach ($boards['boards'] as $board)
                                    <option value="{{ $board['id'] }}"
                                        {{ old('pushboard_id', $settings['pushboard_id'] ?? '') == $board['id'] ? 'selected' : '' }}>
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

                            <button type="button" class="btn btn-primary" id="btn-add">
                                {{ __('Add New Item') }}
                            </button>
                            <span class="connection-status"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{ __('Items') }}</label>
                        <div class="col-sm-9">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Settings</th>
                                    </tr>
                                </thead>
                                <tbody id="tblItems">
                                </tbody>
                            </table>
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
                                    <input type="checkbox" name="push_on_conversation_updated" value="1"
                                        {{ old('push_on_conversation_updated', $settings['push_on_conversation_updated'] ?? '') ? 'checked' : '' }}>
                                    {{ __('When a conversation is updated') }}
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="push_on_conversation_closed" value="1"
                                        {{ old('push_on_conversation_closed', $settings['push_on_conversation_closed'] ?? '') ? 'checked' : '' }}>
                                    {{ __('When conversation closed') }}
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="push_on_conversation_fully_closed" value="1"
                                        {{ old('push_on_conversation_fully_closed', $settings['push_on_conversation_fully_closed'] ?? '') ? 'checked' : '' }}>
                                    {{ __('When conversation fully closed & Send full conversation attachment') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{ __('HTML Display Method') }}</label>
                        <div class="col-sm-9">
                            <select name="html_display_method" class="form-control">
                                <option value="update" {{ old('html_display_method', $settings['html_display_method'] ?? '') == 'update' ? 'selected' : '' }}>
                                    {{ __('As Update (Comment)') }}
                                </option>
                                <option value="attachment" {{ old('html_display_method', $settings['html_display_method'] ?? '') == 'attachment' ? 'selected' : '' }}>
                                    {{ __('As File Attachment') }}
                                </option>
                            </select>
                            <p class="help-block">
                                {{ __('Choose how HTML conversation content will be displayed in Monday.com') }}
                            </p>
                        </div>
                    </div>

                    <div class="form-group" style="display: none">
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

            <div class="form-horizontal">
                <div class="form-section">
                    <h3>{{ __('Create Pushboard') }}</h3>
                    <p class="help-block">
                        {{ __('Create Monday.com Board') }}
                    </p>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{ __('Board Name') }}</label>
                        <div class="col-sm-9">
                            <input type="text" id="board_name" class="form-control" />
                            <p class="help-block">
                                {{ __('Enter a name for your new board') }}
                            </p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="board_kind">{{ __('Board Visibility') }}</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="board_kind">
                                <option value="public">{{ __('Public') }}</option>
                                <option value="private">{{ __('Private') }}</option>
                                <option value="share">{{ __('Shareable') }}</option>
                            </select>
                            <p class="help-block">
                                {{ __('Select the visibility level for your board') }}
                            </p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="fields">{{ __('Fields') }}</label>
                        <div class="col-sm-9">
                            <select id="fields" name="fields" class="form-control">
                                <option value="all" selected>{{ __('Select All') }}</option>
                                @foreach ($fields as $category => $items)
                                    <optgroup label="{{ $category }}">
                                        @foreach ($items as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}
                                                ({{ $key }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>

                            <p class="help-block">
                                {{ __('Select the visibility level for your board') }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="form-group margin-top">
                    <div class="col-sm-offset-3 col-sm-9">
                        <button id="btn-create-board" style="position: relative; display: flex; gap: 0.5rem; overflow: hidden;" class="btn btn-primary">
                            Create a Pushboard
                        </button>
                    </div>
                </div>
            </div>
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
<style>
    .modal-backdrop {
        z-index: 999 !important;
    }
</style>

<!-- Modal Structure -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Create New Item</h5>
            </div>
            <!-- Modal Body -->
            <div class="modal-body">
                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Item Name') }}</label>
                    <div class="col-sm-9">
                        <input type="text" id="item_name" class="form-control" />
                    </div>
                </div>
            </div>
            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                    id="btn-close">Close</button>
                <button type="button" class="btn btn-primary" id="btn-item-save">Save changes</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.onload = function() {
        const loadItems = () => {
            var board_id = $("#pushboard_id").val();
            if (board_id == "") {
                $("#tblItems").html("");
                return;
            }
            $("#selected_id").val(board_id);

            var url = "{{ route('monday.get.board-items') }}";
            var data = {
                board_id: board_id
            }
            $.post(url, data, (res) => {
                var html = '';
                for (var i = 0; i < res.items_page.items.length; i++) {
                    var item = res.items_page.items[i];
                    html += `
                        <tr id="tr${item.id}">
                            <td>${item.id}</td>
                            <td>${item.name}</td>
                            <td><button type="button" data-item-id="${item.id}" class="btnSettings btn btn-danger">Delete</button></td>
                        </tr>
                    `
                }
                $("#tblItems").html(html);
            })
        }

        $(document).on("click", ".btnSettings", function() {
            var itemId = $(this).data("item-id");
            if (!confirm(`Are you sure to delete this item(${itemId})?`))
                return;

            var data = {
                itemId: itemId
            };
            var url = "{{ route('monday.delete.board-item') }}"
            $.post(url, data, (res) => {
                $(`#tr${itemId}`).remove();
            })
        });

        $("#pushboard_id").on('change', loadItems)
        $("#btn-create-board").on('click', function() {
            var board_name = $("#board_name").val();
            if (board_name == '') {
                showFloatingAlert('error', 'Please input board name');
                return;
            }
            $("#btn-create-board").attr('disabled', true);
            var board_kind = $("#board_kind").val();
            var fields = $("#fields").val();
            var url = "{{ route('monday.board.save') }}";
            $("#btn-create-board").html(`<span class="loading-spinner"></span> Processing...`);
            $.post(url, {
                board_name: board_name,
                board_kind: board_kind,
                fields: fields,
            }, (res) => {
                showFloatingAlert('success',
                    `You pushedboard was created successfully. Pushboard name: "${board_name}" id: ${res.board_id}`
                    );
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            })
        });

        // Test board connection
        $('.test-board-connection').on('click', function() {
            var $button = $(this);
            var $status = $('.connection-status');
            var apiToken = $('input[name="api_token"]').val();
            var boardId = $('select[name="pushboard_id"]').val();

            if (!apiToken) {
                $status.removeClass('connection-success').addClass('connection-error').text(
                    '{{ __('API token is required') }}');
                return;
            }

            if (!boardId) {
                $status.removeClass('connection-success').addClass('connection-error').text(
                    '{{ __('Please select a board') }}');
                return;
            }

            $button.prop('disabled', true);
            $status.removeClass('connection-success connection-error').text(
                '{{ __('Testing connection...') }}');

            $.ajax({
                url: '{{ route('monday.test-board-connection') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    api_token: apiToken,
                    board_id: boardId
                },
                success: function(response) {
                    $status.removeClass('connection-error').addClass('connection-success').text(
                        response.message);
                },
                error: function(xhr) {
                    var errorMessage = '{{ __('Connection failed') }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    $status.removeClass('connection-success').addClass('connection-error').text(
                        errorMessage);
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });

        $("#btn-add").on('click', function() {
            $("#exampleModal").modal();
        })

        $("#btn-close").on('click', function() {
            $("#exampleModal").modal('hide');
        })

        $("#btn-item-save").on('click', function() {
            var item_name = $("#item_name").val();
            if (item_name == "")
                return;

            var data = {
                board_id: $("#pushboard_id").val(),
                item_name: item_name
            }
            var url = "{{ route('monday.create.board-item') }}"
            $.post(url, data, (res) => {
                loadItems();
                $("#exampleModal").modal('hide');
            })
        })

        $('#chk_attachments').on('change', function() {
            $("#push_attachments").val(this.checked ? '1' : '0')
            console.log($("#push_attachments").val())
        });

        $("#btnApply").on('click', function() {
            var selected_id = $("#selected_id").val();

            var exists = $("#pushboard_id").find(`option[value="${selected_id}"]`).length > 0;
            if (!exists) {
                alert("Invalid Monday board ID provided. Please verify the ID and try again.")
                return;
            }

            $("#pushboard_id").val(selected_id);
            loadItems();
        })

        loadItems();
    };
</script>

<style>
    .loading-btn .loading-spinner+span {
        transition: opacity 0.3s ease;
    }

    .loading-btn:disabled .loading-spinner+span {
        opacity: 0;
    }

    /* Circular Spinner */
    .loading-spinner {
        margin-top: 0.6rem;
        width: 1.4rem;
        height: 1.4rem;
        border: 2px solid #fff;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    /* Optional: Fade out text and scale spinner slightly when active */
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
