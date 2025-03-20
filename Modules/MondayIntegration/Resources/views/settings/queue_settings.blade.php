@extends('layouts.app')

@section('title', 'Monday.com Integration - Queue Settings')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>{{ __('Monday.com Integration - Queue Settings') }}</h1>

            @include('partials/flash_messages')

            <div class="margin-top margin-bottom">
                <a href="{{ route('monday.settings') }}" class="btn btn-link"><i class="glyphicon glyphicon-arrow-left"></i> {{ __('Back to Settings') }}</a>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">{{ __('Queue Settings') }}</h3>
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('monday.save_queue_settings') }}">
                        {{ csrf_field() }}

                        <div class="form-group">
                            <label for="async_mode">{{ __('Default Processing Mode') }}</label>
                            <select name="async_mode" id="async_mode" class="form-control">
                                <option value="sync" {{ $settings['async_mode'] == 'sync' ? 'selected' : '' }}>{{ __('Synchronous (Process Immediately)') }}</option>
                                <option value="async" {{ $settings['async_mode'] == 'async' ? 'selected' : '' }}>{{ __('Asynchronous (Queue for Background Processing)') }}</option>
                                <option value="batch" {{ $settings['async_mode'] == 'batch' ? 'selected' : '' }}>{{ __('Batch Mode (Group Similar Operations)') }}</option>
                            </select>
                            <p class="help-block">{{ __('Determines how updates to Monday.com are processed by default.') }}</p>
                        </div>

                        <div class="form-group">
                            <label for="high_load_threshold">{{ __('High Load Threshold') }}</label>
                            <input type="number" name="high_load_threshold" id="high_load_threshold" class="form-control" value="{{ $settings['high_load_threshold'] }}" min="1" max="1000">
                            <p class="help-block">{{ __('Number of pending jobs that triggers high load mode, which delays low-priority jobs.') }}</p>
                        </div>

                        <div class="form-group">
                            <label for="default_priority">{{ __('Default Job Priority') }}</label>
                            <select name="default_priority" id="default_priority" class="form-control">
                                <option value="high" {{ $settings['default_priority'] == 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                                <option value="default" {{ $settings['default_priority'] == 'default' ? 'selected' : '' }}>{{ __('Default') }}</option>
                                <option value="low" {{ $settings['default_priority'] == 'low' ? 'selected' : '' }}>{{ __('Low') }}</option>
                            </select>
                            <p class="help-block">{{ __('Default priority for jobs when not specified.') }}</p>
                        </div>

                        <div class="form-group">
                            <label>{{ __('Priority Settings') }}</label>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Operation') }}</th>
                                            <th>{{ __('Priority') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>{{ __('New Conversation Sync') }}</td>
                                            <td>
                                                <select name="priority_new_conversation" class="form-control">
                                                    <option value="high" {{ $settings['priority_new_conversation'] == 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                                                    <option value="default" {{ $settings['priority_new_conversation'] == 'default' ? 'selected' : '' }}>{{ __('Default') }}</option>
                                                    <option value="low" {{ $settings['priority_new_conversation'] == 'low' ? 'selected' : '' }}>{{ __('Low') }}</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Conversation Update') }}</td>
                                            <td>
                                                <select name="priority_conversation_update" class="form-control">
                                                    <option value="high" {{ $settings['priority_conversation_update'] == 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                                                    <option value="default" {{ $settings['priority_conversation_update'] == 'default' ? 'selected' : '' }}>{{ __('Default') }}</option>
                                                    <option value="low" {{ $settings['priority_conversation_update'] == 'low' ? 'selected' : '' }}>{{ __('Low') }}</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('New Thread') }}</td>
                                            <td>
                                                <select name="priority_new_thread" class="form-control">
                                                    <option value="high" {{ $settings['priority_new_thread'] == 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                                                    <option value="default" {{ $settings['priority_new_thread'] == 'default' ? 'selected' : '' }}>{{ __('Default') }}</option>
                                                    <option value="low" {{ $settings['priority_new_thread'] == 'low' ? 'selected' : '' }}>{{ __('Low') }}</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Status Update') }}</td>
                                            <td>
                                                <select name="priority_status_update" class="form-control">
                                                    <option value="high" {{ $settings['priority_status_update'] == 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                                                    <option value="default" {{ $settings['priority_status_update'] == 'default' ? 'selected' : '' }}>{{ __('Default') }}</option>
                                                    <option value="low" {{ $settings['priority_status_update'] == 'low' ? 'selected' : '' }}>{{ __('Low') }}</option>
                                                </select>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="retry_failed_jobs" value="1" {{ $settings['retry_failed_jobs'] ? 'checked' : '' }}>
                                    {{ __('Automatically retry failed jobs') }}
                                </label>
                            </div>
                            <p class="help-block">{{ __('If enabled, failed jobs will be retried automatically with exponential backoff.') }}</p>
                        </div>

                        <div class="form-group">
                            <label for="max_retries">{{ __('Maximum Retries') }}</label>
                            <input type="number" name="max_retries" id="max_retries" class="form-control" value="{{ $settings['max_retries'] }}" min="1" max="10">
                            <p class="help-block">{{ __('Maximum number of times to retry a failed job before giving up.') }}</p>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">{{ __('Save Settings') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">{{ __('Current Queue Status') }}</h3>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('Queue') }}</th>
                                    <th>{{ __('Pending Jobs') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ __('High Priority') }}</td>
                                    <td>{{ $metrics['high'] }}</td>
                                </tr>
                                <tr>
                                    <td>{{ __('Default Priority') }}</td>
                                    <td>{{ $metrics['default'] }}</td>
                                </tr>
                                <tr>
                                    <td>{{ __('Low Priority') }}</td>
                                    <td>{{ $metrics['low'] }}</td>
                                </tr>
                                <tr class="active">
                                    <td><strong>{{ __('Total') }}</strong></td>
                                    <td><strong>{{ $metrics['total'] }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert {{ $metrics['high_load'] ? 'alert-warning' : 'alert-success' }}">
                        <strong>{{ __('Load Status:') }}</strong> 
                        {{ $metrics['high_load'] ? __('HIGH LOAD - Some jobs may be delayed') : __('Normal') }}
                    </div>

                    <div class="form-group">
                        <a href="{{ route('monday.reset_queue_metrics') }}" class="btn btn-default">
                            <i class="glyphicon glyphicon-refresh"></i> {{ __('Reset Queue Metrics') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
