@extends('layouts.app')

@section('title', __('Monday.com Dashboard'))

@section('content')
<div class="section-heading">
    {{ __('Monday.com Integration Dashboard') }}
</div>

<div class="row-container">
    <div class="row">
        <!-- Connection Status -->
        <div class="col-sm-6 col-md-3">
            <div class="dashboard-widget">
                <div class="widget-header">
                    <i class="glyphicon glyphicon-signal"></i>
                    {{ __('Connection Status') }}
                </div>
                <div class="widget-body">
                    @if($isConnected)
                        <div class="status-indicator success">
                            <i class="glyphicon glyphicon-ok"></i>
                            {{ __('Connected') }}
                        </div>
                    @else
                        <div class="status-indicator error">
                            <i class="glyphicon glyphicon-remove"></i>
                            {{ __('Disconnected') }}
                        </div>
                    @endif
                    <div class="last-sync">
                        {{ __('Last Sync') }}: {{ $lastSync ? $lastSync->diffForHumans() : __('Never') }}
                    </div>
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
                        <span>{{ $totalSynced }}</span>
                    </div>
                    <div class="stat-item">
                        <label>{{ __('Pending Syncs') }}:</label>
                        <span>{{ $pendingSyncs }}</span>
                    </div>
                    <div class="stat-item">
                        <label>{{ __('Failed Syncs') }}:</label>
                        <span class="{{ $failedSyncs > 0 ? 'text-danger' : '' }}">{{ $failedSyncs }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Webhooks -->
        <div class="col-sm-6 col-md-3">
            <div class="dashboard-widget">
                <div class="widget-header">
                    <i class="glyphicon glyphicon-transfer"></i>
                    {{ __('Webhooks') }}
                </div>
                <div class="widget-body">
                    <div class="stat-item">
                        <label>{{ __('Active Webhooks') }}:</label>
                        <span>{{ $activeWebhooks }}</span>
                    </div>
                    <div class="stat-item">
                        <label>{{ __('Recent Events') }}:</label>
                        <span>{{ $recentWebhookEvents }}</span>
                    </div>
                    <div class="stat-item">
                        <label>{{ __('Failed Events') }}:</label>
                        <span class="{{ $failedWebhookEvents > 0 ? 'text-danger' : '' }}">{{ $failedWebhookEvents }}</span>
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
                        <a href="{{ route('monday.settings') }}" class="btn btn-default">
                            <i class="glyphicon glyphicon-cog"></i>
                            {{ __('Settings') }}
                        </a>
                        <a href="{{ route('monday.test-connection') }}" class="btn btn-default test-connection">
                            <i class="glyphicon glyphicon-refresh"></i>
                            {{ __('Test Connection') }}
                        </a>
                        <a href="{{ route('monday.sync-now') }}" class="btn btn-primary sync-now">
                            <i class="glyphicon glyphicon-refresh"></i>
                            {{ __('Sync Now') }}
                        </a>
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
                                    <td>{{ $activity->action_type }}</td>
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
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Test Connection
    $('.test-connection').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.prop('disabled', true);
        
        $.ajax({
            url: btn.attr('href'),
            method: 'POST',
            success: function(response) {
                showFloatingAlert('success', response.message);
            },
            error: function(xhr) {
                showFloatingAlert('danger', xhr.responseJSON.message || 'Connection test failed');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Sync Now
    $('.sync-now').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.prop('disabled', true);
        
        $.ajax({
            url: btn.attr('href'),
            method: 'POST',
            success: function(response) {
                showFloatingAlert('success', response.message);
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            },
            error: function(xhr) {
                showFloatingAlert('danger', xhr.responseJSON.message || 'Sync failed');
                btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
