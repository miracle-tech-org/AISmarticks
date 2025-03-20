@extends('layouts.app')

@section('title', __('Queue Settings'))

@section('content')
<div class="section-heading">
    {{ __('Monday.com Integration - Queue Settings') }}
</div>

<div class="row-container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>{{ __('Queue Configuration') }}</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('mondayintegration.queue_settings.save') }}">
                        {{ csrf_field() }}
                        
                        <div class="form-group">
                            <label for="high_load_threshold">{{ __('High Load Threshold') }}</label>
                            <input type="number" class="form-control" id="high_load_threshold" name="high_load_threshold" 
                                value="{{ $settings['high_load_threshold'] }}" min="1" max="1000" required>
                            <small class="form-text text-muted">{{ __('Number of jobs in queue that triggers high load warning') }}</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="job_timeout">{{ __('Job Timeout (seconds)') }}</label>
                            <input type="number" class="form-control" id="job_timeout" name="job_timeout" 
                                value="{{ $settings['job_timeout'] }}" min="30" max="3600" required>
                            <small class="form-text text-muted">{{ __('Maximum time a job can run before timing out') }}</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="max_retries">{{ __('Maximum Retries') }}</label>
                            <input type="number" class="form-control" id="max_retries" name="max_retries" 
                                value="{{ $settings['max_retries'] }}" min="0" max="10" required>
                            <small class="form-text text-muted">{{ __('Maximum number of times to retry failed jobs') }}</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="retry_delay">{{ __('Retry Delay (minutes)') }}</label>
                            <input type="number" class="form-control" id="retry_delay" name="retry_delay" 
                                value="{{ $settings['retry_delay'] }}" min="1" max="60" required>
                            <small class="form-text text-muted">{{ __('Base delay before retrying a failed job (uses exponential backoff)') }}</small>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">{{ __('Save Settings') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3>{{ __('Current Queue Status') }}</h3>
                </div>
                <div class="card-body">
                    <div class="queue-stats">
                        <div class="stat-item">
                            <label>{{ __('High Priority Jobs') }}:</label>
                            <span class="badge {{ $queueMetrics['high'] > 0 ? 'badge-warning' : 'badge-success' }}">{{ $queueMetrics['high'] }}</span>
                        </div>
                        
                        <div class="stat-item">
                            <label>{{ __('Default Priority Jobs') }}:</label>
                            <span class="badge {{ $queueMetrics['default'] > 0 ? 'badge-info' : 'badge-success' }}">{{ $queueMetrics['default'] }}</span>
                        </div>
                        
                        <div class="stat-item">
                            <label>{{ __('Low Priority Jobs') }}:</label>
                            <span class="badge {{ $queueMetrics['low'] > 0 ? 'badge-info' : 'badge-success' }}">{{ $queueMetrics['low'] }}</span>
                        </div>
                        
                        <div class="stat-item">
                            <label>{{ __('Total Jobs') }}:</label>
                            <span class="badge {{ $queueMetrics['total'] > 0 ? 'badge-primary' : 'badge-success' }}">{{ $queueMetrics['total'] }}</span>
                        </div>
                        
                        <div class="stat-item">
                            <label>{{ __('Failed Jobs') }}:</label>
                            <span class="badge {{ $queueMetrics['failed'] > 0 ? 'badge-danger' : 'badge-success' }}">{{ $queueMetrics['failed'] }}</span>
                        </div>
                        
                        <div class="stat-item">
                            <label>{{ __('Queue Status') }}:</label>
                            <span class="badge {{ $queueMetrics['high_load'] ? 'badge-danger' : 'badge-success' }}">
                                {{ $queueMetrics['high_load'] ? __('High Load') : __('Normal') }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="queue-actions mt-4">
                        <a href="{{ route('mondayintegration.dashboard') }}" class="btn btn-default btn-block">
                            <i class="glyphicon glyphicon-dashboard"></i> {{ __('Back to Dashboard') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('css')
<style>
    .queue-stats .stat-item {
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .queue-stats .badge {
        font-size: 14px;
        padding: 5px 10px;
    }
    
    .badge-success {
        background-color: #28a745;
    }
    
    .badge-info {
        background-color: #17a2b8;
    }
    
    .badge-warning {
        background-color: #ffc107;
        color: #212529;
    }
    
    .badge-danger {
        background-color: #dc3545;
    }
    
    .badge-primary {
        background-color: #007bff;
    }
    
    .mt-4 {
        margin-top: 25px;
    }
</style>
@endpush
@endsection
