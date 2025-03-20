@extends('layouts.app')

@section('title', __('Monday.com Dashboard'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
<style>
    .modal-backdrop {
        z-index: 1 !important;
    }
    .modal-content {
        margin-top: 10%;
    }
</style>
<div class="section-heading">
    {{ __('Monday.com Integration Dashboard') }}
</div>

<div class="row-container">
    <!-- Status Overview Row -->
    <div class="row">
        <!-- Connection Status -->
        <div class="col-sm-6 col-md-3">
            <div class="dashboard-widget">
                <div class="widget-header">
                    <i class="glyphicon glyphicon-signal"></i>
                    {{ __('Connection Status') }}
                </div>
                <div class="widget-body">
                    @if($connectionStatus['is_connected'])
                        <div class="status-indicator success">
                            <i class="glyphicon glyphicon-ok"></i>
                            {{ __('Connected') }}
                        </div>
                        <div class="api-info">
                            @if($connectionStatus['api_version'])
                                <div class="stat-item">
                                    <label>{{ __('API Version') }}:</label>
                                    <span>{{ $connectionStatus['api_version'] }}</span>
                                </div>
                            @endif
                            @if($connectionStatus['last_successful_connection'])
                                <div class="stat-item">
                                    <label>{{ __('Last Connected') }}:</label>
                                    <span>{{ $connectionStatus['last_successful_connection']->diffForHumans() }}</span>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="status-indicator error">
                            <i class="glyphicon glyphicon-remove"></i>
                            {{ __('Disconnected') }}
                        </div>
                        @if($connectionStatus['error_message'])
                            <div class="error-message">
                                {{ $connectionStatus['error_message'] }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <!-- Sync Stats -->
        <div class="col-sm-6 col-md-3">
            <div class="dashboard-widget">
                <div class="widget-header">
                    <i class="glyphicon glyphicon-refresh"></i>
                    {{ __('Sync Statistics') }}
                </div>
                <div class="widget-body">
                    <div class="stat-item">
                        <label>{{ __('Total Synced') }}:</label>
                        <span>{{ $syncStats['total_synced'] }}</span>
                    </div>
                    <div class="stat-item">
                        <label>{{ __('Pending Syncs') }}:</label>
                        <span>{{ $syncStats['pending_syncs'] }}</span>
                    </div>
                    <div class="stat-item">
                        <label>{{ __('Failed Syncs') }}:</label>
                        <span class="{{ $syncStats['failed_syncs'] > 0 ? 'text-danger' : '' }}">{{ $syncStats['failed_syncs'] }}</span>
                    </div>
                    <div class="stat-item">
                        <label>{{ __('Last Sync') }}:</label>
                        <span>{{ $syncStats['last_sync'] ? $syncStats['last_sync']->diffForHumans() : __('Never') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Queue Status -->
        <div class="col-sm-6 col-md-3">
            <div class="dashboard-widget">
                <div class="widget-header">
                    <i class="glyphicon glyphicon-tasks"></i>
                    {{ __('Queue Status') }}
                </div>
                <div class="widget-body">
                    <div class="stat-item">
                        <label>{{ __('High Priority Jobs') }}:</label>
                        <span>{{ $queueStats['high'] }}</span>
                    </div>
                    <div class="stat-item">
                        <label>{{ __('Default Priority Jobs') }}:</label>
                        <span>{{ $queueStats['default'] }}</span>
                    </div>
                    <div class="stat-item">
                        <label>{{ __('Low Priority Jobs') }}:</label>
                        <span>{{ $queueStats['low'] }}</span>
                    </div>
                    <div class="stat-item">
                        <label>{{ __('Load Status') }}:</label>
                        <span class="{{ $queueStats['high_load'] ? 'text-warning' : 'text-success' }}">
                            {{ $queueStats['high_load'] ? __('High Load') : __('Normal') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-sm-6 col-md-3">
            <div class="dashboard-widget">
                <div class="widget-header">
                    <i class="glyphicon glyphicon-flash"></i>
                    {{ __('Quick Actions') }}
                </div>
                <div class="widget-body">
                    <div class="action-buttons">
                        <a href="" class="btn btn-default">
                            <i class="glyphicon glyphicon-cog"></i>
                            {{ __('Settings') }}
                        </a>
                        <a class="btn btn-default test-connection">
                            <i class="glyphicon glyphicon-refresh"></i>
                            {{ __('Test Connection') }}
                        </a>
                        <a href="" class="btn btn-default">
                            <i class="glyphicon glyphicon-tasks"></i>
                            {{ __('Queue Settings') }}
                        </a>
                        <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#syncModal">
                            <i class="glyphicon glyphicon-refresh"></i>
                            {{ __('Sync Now') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mapping Statistics Row -->
    <div class="row">
        <!-- Conversation Mappings -->
        <div class="col-md-4">
            <div class="dashboard-widget">
                <div class="widget-header">
                    <i class="glyphicon glyphicon-envelope"></i>
                    {{ __('Conversation Mappings') }}
                </div>
                <div class="widget-body">
                    <div class="stat-item">
                        <label>{{ __('Total Mappings') }}:</label>
                        <span>{{ $mappingStats['conversation_mappings']['total'] }}</span>
                    </div>
                    
                    @if(count($mappingStats['conversation_mappings']['by_board']) > 0)
                        <div class="stat-breakdown">
                            <label>{{ __('By Board') }}:</label>
                            <ul class="stat-list">
                                @foreach($mappingStats['conversation_mappings']['by_board'] as $boardId => $count)
                                    <li>
                                        <span class="board-name">{{ __('Board') }} #{{ $boardId }}:</span>
                                        <span class="count">{{ $count }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    @if($mappingStats['conversation_mappings']['last_created'])
                        <div class="stat-item">
                            <label>{{ __('Last Created') }}:</label>
                            <span>{{ $mappingStats['conversation_mappings']['last_created']->created_at->diffForHumans() }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Caller ID Mappings -->
        <div class="col-md-4">
            <div class="dashboard-widget">
                <div class="widget-header">
                    <i class="glyphicon glyphicon-phone-alt"></i>
                    {{ __('Caller ID Mappings') }}
                </div>
                <div class="widget-body">
                    <div class="stat-item">
                        <label>{{ __('Total Mappings') }}:</label>
                        <span>{{ $mappingStats['caller_id_mappings']['total'] }}</span>
                    </div>
                    
                    @if(count($mappingStats['caller_id_mappings']['by_board']) > 0)
                        <div class="stat-breakdown">
                            <label>{{ __('By Board') }}:</label>
                            <ul class="stat-list">
                                @foreach($mappingStats['caller_id_mappings']['by_board'] as $boardId => $count)
                                    <li>
                                        <span class="board-name">{{ __('Board') }} #{{ $boardId }}:</span>
                                        <span class="count">{{ $count }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    @if(count($mappingStats['caller_id_mappings']['by_channel']) > 0)
                        <div class="stat-breakdown">
                            <label>{{ __('By Channel') }}:</label>
                            <ul class="stat-list">
                                @foreach($mappingStats['caller_id_mappings']['by_channel'] as $channel => $count)
                                    <li>
                                        <span class="channel-name">{{ ucfirst($channel) }}:</span>
                                        <span class="count">{{ $count }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Field Mappings -->
        <div class="col-md-4">
            <div class="dashboard-widget">
                <div class="widget-header">
                    <i class="glyphicon glyphicon-list-alt"></i>
                    {{ __('Field Mappings') }}
                </div>
                <div class="widget-body">
                    <div class="stat-item">
                        <label>{{ __('Conversation Fields') }}:</label>
                        <span>{{ $mappingStats['field_mappings']['conversation'] }}</span>
                    </div>
                    <div class="stat-item">
                        <label>{{ __('Customer Fields') }}:</label>
                        <span>{{ $mappingStats['field_mappings']['customer'] }}</span>
                    </div>
                    <div class="stat-item">
                        <label>{{ __('Thread Fields') }}:</label>
                        <span>{{ $mappingStats['field_mappings']['thread'] }}</span>
                    </div>
                    <div class="action-link">
                        <a href="" class="btn btn-sm btn-default">
                            <i class="glyphicon glyphicon-pencil"></i>
                            {{ __('Manage Field Mappings') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Troubleshooting Section -->
    @if(count($troubleshootingInfo['issues']) > 0)
    <div class="row">
        <div class="col-md-12">
            <div class="dashboard-widget">
                <div class="widget-header">
                    <i class="glyphicon glyphicon-warning-sign"></i>
                    {{ __('Troubleshooting') }}
                </div>
                <div class="widget-body">
                    <div class="issues-list">
                        @foreach($troubleshootingInfo['issues'] as $issue)
                            <div class="issue-item {{ $issue['type'] }}">
                                <div class="issue-icon">
                                    <i class="glyphicon {{ $issue['type'] == 'error' ? 'glyphicon-remove-circle' : 'glyphicon-exclamation-sign' }}"></i>
                                </div>
                                <div class="issue-content">
                                    <div class="issue-message">{{ $issue['message'] }}</div>
                                    <div class="issue-solution">{{ $issue['solution'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- System Information -->
    <div class="row">
        <div class="col-md-12">
            <div class="dashboard-widget">
                <div class="widget-header">
                    <i class="glyphicon glyphicon-info-sign"></i>
                    {{ __('System Information') }}
                </div>
                <div class="widget-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="stat-item">
                                <label>{{ __('PHP Version') }}:</label>
                                <span>{{ $troubleshootingInfo['system_info']['php_version'] }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-item">
                                <label>{{ __('Laravel Version') }}:</label>
                                <span>{{ $troubleshootingInfo['system_info']['laravel_version'] }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-item">
                                <label>{{ __('Module Version') }}:</label>
                                <span>{{ $troubleshootingInfo['system_info']['module_version'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-md-12">
            <div class="dashboard-widget">
                <div class="widget-header">
                    <i class="glyphicon glyphicon-time"></i>
                    {{ __('Recent Activity') }}
                </div>
                <div class="widget-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Time') }}</th>
                                <th>{{ __('Action') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Details') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentActivity as $activity)
                                <tr>
                                    <td>{{ $activity->created_at->diffForHumans() }}</td>
                                    <td>{{ ucfirst($activity->action_type) }}</td>
                                    <td>
                                        <span class="status-badge {{ $activity->status }}">
                                            {{ ucfirst($activity->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($activity->error_message)
                                            <span class="text-danger">{{ $activity->error_message }}</span>
                                        @else
                                            {{ $activity->details }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">{{ __('No recent activity') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sync Modal -->
<div class="modal fade" id="syncModal" tabindex="-1" role="dialog" aria-labelledby="syncModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="syncModalLabel">{{ __('Manual Sync Options') }}</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="syncScope">{{ __('Sync Scope') }}</label>
                    <select id="syncScope" class="form-control">
                        <option value="all">{{ __('All Conversations') }}</option>
                        <option value="recent">{{ __('Recent Conversations') }}</option>
                        <option value="unsync">{{ __('Unsynced Conversations') }}</option>
                        <option value="status">{{ __('By Status') }}</option>
                    </select>
                </div>
                
                <div class="form-group sync-status-group" style="display: none;">
                    <label for="syncStatus">{{ __('Conversation Status') }}</label>
                    <select id="syncStatus" class="form-control">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="closed">{{ __('Closed') }}</option>
                        <option value="spam">{{ __('Spam') }}</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="syncLimit">{{ __('Limit') }}</label>
                    <select id="syncLimit" class="form-control">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50" selected>50</option>
                        <option value="100">100</option>
                    </select>
                    <small class="text-muted">{{ __('Maximum number of conversations to sync') }}</small>
                </div>
                
                <div class="form-group">
                    <label for="syncBoardId">{{ __('Target Board') }}</label>
                    <select id="syncBoardId" class="form-control">
                        <option value="">{{ __('Default Board') }}</option>
                        @foreach(json_decode(app('Modules\MondayIntegration\Services\SecurityService')->getOption('boards', '[]'), true) as $board)
                            <option value="{{ $board['id'] }}">{{ $board['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="alert alert-info">
                    <i class="glyphicon glyphicon-info-sign"></i>
                    {{ __('This will queue a sync job that will run in the background. You can check the progress in the Recent Activity section.') }}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="syncNowBtn">{{ __('Start Sync') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- Alerts Container -->
<div class="alerts-container" style="position: fixed; top: 20px; right: 20px; z-index: 99999; width: 350px;"></div>

@push('css')
<style>
    .dashboard-widget {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 20px;
        box-shadow: 0 1px 1px rgba(0,0,0,.05);
    }

    .widget-header {
        background: #f5f5f5;
        border-bottom: 1px solid #ddd;
        padding: 10px 15px;
        font-weight: bold;
    }

    .widget-body {
        padding: 15px;
    }

    .status-indicator {
        font-size: 16px;
        margin-bottom: 10px;
    }

    .status-indicator.success {
        color: #5cb85c;
    }

    .status-indicator.error {
        color: #d9534f;
    }

    .stat-item {
        margin-bottom: 8px;
    }

    .stat-item label {
        font-weight: normal;
        margin-right: 5px;
    }

    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .status-badge {
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
    }

    .status-badge.completed {
        background: #dff0d8;
        color: #3c763d;
    }

    .status-badge.failed {
        background: #f2dede;
        color: #a94442;
    }

    .status-badge.pending {
        background: #fcf8e3;
        color: #8a6d3b;
    }

    .status-badge.processing {
        background: #d9edf7;
        color: #31708f;
    }
    
    .error-message {
        color: #d9534f;
        margin-top: 10px;
        font-size: 12px;
    }
    
    .stat-breakdown {
        margin-top: 15px;
    }
    
    .stat-list {
        list-style: none;
        padding-left: 10px;
        margin-top: 5px;
    }
    
    .stat-list li {
        display: flex;
        justify-content: space-between;
        margin-bottom: 3px;
    }
    
    .action-link {
        margin-top: 15px;
    }
    
    .issues-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .issue-item {
        display: flex;
        padding: 10px;
        border-radius: 4px;
    }
    
    .issue-item.error {
        background-color: #f2dede;
        border-left: 4px solid #d9534f;
    }
    
    .issue-item.warning {
        background-color: #fcf8e3;
        border-left: 4px solid #f0ad4e;
    }
    
    .issue-icon {
        margin-right: 15px;
        font-size: 20px;
    }
    
    .issue-item.error .issue-icon {
        color: #d9534f;
    }
    
    .issue-item.warning .issue-icon {
        color: #f0ad4e;
    }
    
    .issue-message {
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .issue-solution {
        font-size: 13px;
        color: #555;
    }
    
    /* Spinner animation */
    .spinning {
        animation: spin 1s infinite linear;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>
@endpush
@endsection

<script>
    window.onload = function() {
        // Test connection button
        $('.test-connection').click(function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var originalText = $btn.html();
            
            $btn.html('<i class="glyphicon glyphicon-refresh spinning"></i> {{ __('Testing...') }}');
            
            $.ajax({
                url: "{{ route('monday.test-connection') }}",
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    showFloatingAlert('success', response.message);
                },
                error: function(xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message 
                        ? xhr.responseJSON.message 
                        : '{{ __('Connection test failed. Please check your settings.') }}';
                    showFloatingAlert('danger', response.message);
                },
                complete: function() {
                    $btn.html(originalText);
                }
            });
        });
        
        // Sync now button in modal
        $('#syncNowBtn').click(function() {
            var $btn = $(this);
            var originalText = $btn.html();
            
            $btn.html('<i class="glyphicon glyphicon-refresh spinning"></i> {{ __('Syncing...') }}').prop('disabled', true);
            
            var formData = {
                scope: $('#syncScope').val(),
                limit: $('#syncLimit').val(),
                status: $('#syncStatus').val(),
                board_id: $('#syncBoardId').val()
            };
            
            $.ajax({
                url: "{{route('monday.sync-now')}}",
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    showFloatingAlert('success', response.message);
                    $('#syncModal').modal('hide');
                },
                error: function(xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message 
                        ? xhr.responseJSON.message 
                        : '{{ __('Sync failed. Please try again.') }}';
                    showFloatingAlert('danger', message);
                },
                complete: function() {
                    $btn.html(originalText).prop('disabled', false);
                }
            });
        });
        
        // Show/hide status dropdown based on scope selection
        $('#syncScope').change(function() {
            if ($(this).val() === 'status') {
                $('.sync-status-group').show();
            } else {
                $('.sync-status-group').hide();
            }
        });
    }
</script>
