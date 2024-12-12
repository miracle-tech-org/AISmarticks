@extends('layouts.app')

@section('title_full', __('Settings').' - '.$section_name)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    <div class="sidebar-title">
        {{ __('Settings') }}
    </div>
    <ul class="sidebar-menu">
        @foreach ($sections as $item_name => $item_info)
            <li @if ($item_name == $section)class="active"@endif><i class="glyphicon glyphicon-{{ $item_info['icon'] }}"></i> <a href="{{ route('settings', ['section' => $item_name]) }}">{{ $item_info['title'] }}</a></li>
        @endforeach
    </ul>
@endsection

@section('content')
    <div class="section-heading">
        {{ $section_name }}
    </div>

    @include('partials/flash_messages')

    <div class="row-container form-container">
        <div class="row">
            <div class="col-xs-12">
                <h3 class="subheader">
                    AI Token Usuage Limit Alert
                </h3>
                <div class="row">
                    <div class="col-xs-12">
                        <div class="form-group">
                            <label for="email_alert" class="col-sm-2 control-label">Email</label>
                    
                            <div class="col-sm-6">
                                <div class="controls">
                                    <div class="onoffswitch-wrap">
                                        <div class="onoffswitch">
                                            <input type="checkbox" name="settings[email_alert]" value="1" id="email_alert" class="onoffswitch-checkbox" checked="checked">
                                            <label class="onoffswitch-label" for="email_alert"></label>
                                        </div>
                                    </div>
                                </div>
                                <p class="help-block">
                                    Send alert if API usage threhold
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12">
                        <div class="form-group">
                            <label for="sms_alert" class="col-sm-2 control-label">SMS</label>
                    
                            <div class="col-sm-6">
                                <div class="controls">
                                    <div class="onoffswitch-wrap">
                                        <div class="onoffswitch">
                                            <input type="checkbox" name="settings[sms_alert]" value="1" id="sms_alert" class="onoffswitch-checkbox" checked="checked">
                                            <label class="onoffswitch-label" for="sms_alert"></label>
                                        </div>
                                    </div>
                                </div>
                                <p class="help-block">
                                    Send alert if API usage threhold
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12">
                        <div class="form-group">
                            <label for="push_alert" class="col-sm-2 control-label">Push Notification</label>
                    
                            <div class="col-sm-6">
                                <div class="controls">
                                    <div class="onoffswitch-wrap">
                                        <div class="onoffswitch">
                                            <input type="checkbox" name="settings[push_alert]" value="1" id="push_alert" class="onoffswitch-checkbox" checked="checked">
                                            <label class="onoffswitch-label" for="push_alert"></label>
                                        </div>
                                    </div>
                                </div>
                                <p class="help-block">
                                    Send alert if API usage threhold
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xs-12">
                @include(\Eventy::filter('settings.view', 'settings/'.$section, $section))
            </div>
        </div>
    </div>

@endsection
