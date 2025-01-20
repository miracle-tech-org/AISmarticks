@extends('layouts.app')

@section('title', __('AI Assistant Settings'))

@section('content')
    <div class="section-heading">
        <h2 class="section-title">{{ __('AI Assistant Settings') }}</h2>
    </div>

    <div class="container">
        <div class="settings-tabs">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#general-settings">
                        {{ __('General') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#sla-settings">
                        {{ __('SLA Configuration') }}
                    </a>
                </li>
            </ul>

            <form method="POST" action="{{ route('my-improvements.settings.update') }}">
                @csrf
                @method('PUT')

                <div class="tab-content mt-4">
                    {{-- General Settings --}}
                    <div class="tab-pane fade show active" id="general-settings">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('General Settings') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="monthly_usage_limit">{{ __('Monthly Usage Limit (tokens)') }}</label>
                                    <input type="number" name="monthly_usage_limit" id="monthly_usage_limit"
                                        class="form-control" value="{{ $settings['monthly_usage_limit'] ?? 10000 }}">
                                </div>

                                <div class="form-group">
                                    <label for="alert_threshold">{{ __('Alert Threshold (%)') }}</label>
                                    <input type="number" name="alert_threshold" id="alert_threshold" class="form-control"
                                        value="{{ $settings['alert_threshold'] ?? 80 }}" min="1" max="100">
                                </div>

                                <div class="form-group">
                                    <label for="timeout">{{ __('Request Timeout (seconds)') }}</label>
                                    <input type="number" name="timeout" id="timeout" class="form-control"
                                        value="{{ $settings['timeout'] ?? 30 }}" min="1" max="60">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SLA Settings --}}
                    <div class="tab-pane fade" id="sla-settings">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('SLA Configuration') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="response_time">{{ __('Response Time (minutes)') }}</label>
                                            <input type="number" name="sla[response_time]" id="response_time"
                                                class="form-control" value="{{ $slaConfig->response_time_minutes ?? 60 }}"
                                                min="1" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="resolution_time">{{ __('Resolution Time (minutes)') }}</label>
                                            <input type="number" name="sla[resolution_time]" id="resolution_time"
                                                class="form-control"
                                                value="{{ $slaConfig->resolution_time_minutes ?? 480 }}" min="1"
                                                required>
                                        </div>
                                    </div>
                                </div>

                                <h4 class="mt-4">{{ __('Priority Adjustments') }}</h4>
                                <div class="row">
                                    @foreach (range(1, 4) as $priority)
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="priority_{{ $priority }}">
                                                    {{ __('Priority :priority Factor', ['priority' => $priority]) }}
                                                </label>
                                                <input type="number" name="sla[priority_adjustments][{{ $priority }}]"
                                                    id="priority_{{ $priority }}" class="form-control"
                                                    value="{{ $slaConfig->priority_adjustments[$priority] ?? 1.0 }}"
                                                    step="0.1" min="0.1" max="5">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="alert alert-info mt-3">
                                    {{ __('Priority factors adjust the response and resolution times. A factor of 2.0 means twice the standard time.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
