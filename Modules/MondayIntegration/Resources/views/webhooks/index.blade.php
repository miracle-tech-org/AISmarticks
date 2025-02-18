@extends('layouts.app')

@section('title', __('Monday.com Webhooks'))

@section('content')
<div class="section-heading">
    {{ __('Monday.com Webhooks') }}
</div>

<div class="row-container">
    <div class="col-md-12">
        <!-- Webhook List -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="panel-title">{{ __('Active Webhooks') }}</h3>
                    </div>
                    <div class="col-sm-6 text-right">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#create-webhook-modal">
                            <i class="glyphicon glyphicon-plus"></i> {{ __('Add Webhook') }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                @if($webhooks->isEmpty())
                    <div class="text-center text-muted">
                        <p>{{ __('No webhooks configured') }}</p>
                        <p>{{ __('Click "Add Webhook" to create your first webhook') }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Board') }}</th>
                                    <th>{{ __('Event Type') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Last Event') }}</th>
                                    <th>{{ __('Error Count') }}</th>
                                    <th class="text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($webhooks as $webhook)
                                    <tr>
                                        <td>{{ $webhook->board_name }}</td>
                                        <td>{{ $webhook->event_type }}</td>
                                        <td>
                                            <span class="label {{ $webhook->is_active ? 'label-success' : 'label-danger' }}">
                                                {{ $webhook->is_active ? __('Active') : __('Inactive') }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($webhook->last_event_at)
                                                {{ $webhook->last_event_at->diffForHumans() }}
                                            @else
                                                {{ __('Never') }}
                                            @endif
                                        </td>
                                        <td>
                                            <span class="label {{ $webhook->error_count > 0 ? 'label-warning' : 'label-default' }}">
                                                {{ $webhook->error_count }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-default btn-sm test-webhook" 
                                                        data-webhook-id="{{ $webhook->id }}">
                                                    <i class="glyphicon glyphicon-refresh"></i>
                                                </button>
                                                <button type="button" class="btn btn-default btn-sm toggle-webhook" 
                                                        data-webhook-id="{{ $webhook->id }}" 
                                                        data-active="{{ $webhook->is_active }}">
                                                    <i class="glyphicon {{ $webhook->is_active ? 'glyphicon-pause' : 'glyphicon-play' }}"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm delete-webhook" 
                                                        data-webhook-id="{{ $webhook->id }}">
                                                    <i class="glyphicon glyphicon-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Events -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">{{ __('Recent Events') }}</h3>
            </div>
            <div class="panel-body">
                @if($recentEvents->isEmpty())
                    <div class="text-center text-muted">
                        <p>{{ __('No recent webhook events') }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Time') }}</th>
                                    <th>{{ __('Board') }}</th>
                                    <th>{{ __('Event') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Details') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentEvents as $event)
                                    <tr>
                                        <td>{{ $event->created_at->diffForHumans() }}</td>
                                        <td>{{ $event->board_name }}</td>
                                        <td>{{ $event->event_type }}</td>
                                        <td>
                                            <span class="label {{ $event->status === 'success' ? 'label-success' : 'label-danger' }}">
                                                {{ ucfirst($event->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($event->error_message)
                                                <span class="text-danger">{{ $event->error_message }}</span>
                                            @else
                                                {{ $event->details }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Create Webhook Modal -->
<div class="modal fade" id="create-webhook-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('Create Webhook') }}</h4>
            </div>
            <form id="create-webhook-form" action="{{ route('monday.webhooks.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('Board') }}</label>
                        <select name="board_id" class="form-control" required>
                            <option value="">{{ __('Select Board') }}</option>
                            @foreach($boards as $board)
                                <option value="{{ $board['id'] }}">{{ $board['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>{{ __('Event Type') }}</label>
                        <select name="event_type" class="form-control" required>
                            <option value="">{{ __('Select Event Type') }}</option>
                            @foreach($eventTypes as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Create Webhook') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('css')
<style>
    .btn-group {
        display: flex;
    }

    .label {
        display: inline-block;
        min-width: 40px;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Create webhook
    $('#create-webhook-form').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#create-webhook-modal').modal('hide');
                showFloatingAlert('success', response.message);
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            },
            error: function(xhr) {
                showFloatingAlert('danger', xhr.responseJSON.message || 'Failed to create webhook');
            }
        });
    });

    // Test webhook
    $('.test-webhook').click(function() {
        var btn = $(this);
        var webhookId = btn.data('webhook-id');
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: '{{ route("monday.webhooks.test") }}/' + webhookId,
            method: 'POST',
            success: function(response) {
                showFloatingAlert('success', response.message);
            },
            error: function(xhr) {
                showFloatingAlert('danger', xhr.responseJSON.message || 'Failed to test webhook');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Toggle webhook
    $('.toggle-webhook').click(function() {
        var btn = $(this);
        var webhookId = btn.data('webhook-id');
        var isActive = btn.data('active');
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: '{{ route("monday.webhooks.toggle") }}/' + webhookId,
            method: 'POST',
            data: { is_active: !isActive },
            success: function(response) {
                showFloatingAlert('success', response.message);
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            },
            error: function(xhr) {
                showFloatingAlert('danger', xhr.responseJSON.message || 'Failed to toggle webhook');
                btn.prop('disabled', false);
            }
        });
    });

    // Delete webhook
    $('.delete-webhook').click(function() {
        var btn = $(this);
        var webhookId = btn.data('webhook-id');
        
        if (!confirm('{{ __("Are you sure you want to delete this webhook?") }}')) {
            return;
        }
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: '{{ route("monday.webhooks.destroy") }}/' + webhookId,
            method: 'DELETE',
            success: function(response) {
                showFloatingAlert('success', response.message);
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            },
            error: function(xhr) {
                showFloatingAlert('danger', xhr.responseJSON.message || 'Failed to delete webhook');
                btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
