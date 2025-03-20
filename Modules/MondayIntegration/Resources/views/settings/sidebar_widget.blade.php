@extends('layouts.app')

@section('title', __('Monday.com Sidebar Widget Settings'))

@section('content')
<div class="section-heading">
    {{ __('Monday.com Sidebar Widget Settings') }}
</div>

@include('mondayintegration::partials.settings_nav')

<div class="row-container">
    <div class="col-md-12">
        <form action="{{ route('mondayintegration.settings.save') }}" method="POST" class="form-horizontal">
            @csrf

            <!-- Sidebar Widget Settings -->
            <div class="form-section">
                <h3>{{ __('Sidebar Widget Configuration') }}</h3>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Enable Sidebar Widget') }}</label>
                    <div class="col-sm-9">
                        <div class="controls">
                            <div class="onoffswitch-wrap">
                                <div class="onoffswitch">
                                    <input type="checkbox" name="enable_sidebar_widget" class="onoffswitch-checkbox" id="enable_sidebar_widget"
                                           {{ (old('enable_sidebar_widget', $settings['enable_sidebar_widget'] ?? '0') == '1') ? 'checked' : '' }}>
                                    <label class="onoffswitch-label" for="enable_sidebar_widget"></label>
                                </div>
                            </div>
                        </div>
                        <p class="help-block">
                            {{ __('Display Monday.com data in the conversation sidebar.') }}
                        </p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Customer Search Field') }}</label>
                    <div class="col-sm-9">
                        <select name="customer_search_field" class="form-control">
                            <option value="email" {{ (old('customer_search_field', $settings['customer_search_field'] ?? 'email') == 'email') ? 'selected' : '' }}>
                                {{ __('Email') }}
                            </option>
                            <option value="phone" {{ (old('customer_search_field', $settings['customer_search_field'] ?? '') == 'phone') ? 'selected' : '' }}>
                                {{ __('Phone') }}
                            </option>
                            <option value="name" {{ (old('customer_search_field', $settings['customer_search_field'] ?? '') == 'name') ? 'selected' : '' }}>
                                {{ __('Name') }}
                            </option>
                        </select>
                        <p class="help-block">
                            {{ __('Field to use when searching for customer in Monday.com.') }}
                        </p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">{{ __('Quick Actions') }}</label>
                    <div class="col-sm-9">
                        <textarea name="quick_actions_json" class="form-control" rows="8">{{ old('quick_actions_json', $settings['quick_actions'] ?? '[]') }}</textarea>
                        <p class="help-block">
                            {{ __('Configure quick actions for Monday.com items. JSON format:') }}<br>
                            <code>[{"id":"action1","name":"Change Status to Done","type":"status_change","value":"done"},{"id":"action2","name":"Add Comment","type":"add_update","value":"New message from support"}]</code>
                        </p>
                    </div>
                </div>
            </div>

            <div class="form-group margin-top margin-bottom-5">
                <div class="col-sm-9 col-sm-offset-3">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

@endsection

@section('javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function() {
            // Initialize any JavaScript functionality for the sidebar widget settings
        });
    </script>
@endsection
