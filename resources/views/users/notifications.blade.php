@extends('layouts.app')

@section('title_full', __('Notifications').' - '.$user->first_name.' '.$user->last_name)

@if ($user->id == Auth::user()->id)
    @section('body_attrs')@parent data-own_profile="true" @endsection
@endif

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('users/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        {{ __('Notifications') }}
    </div>

    @include('partials/flash_messages')

    <div class="row-container">
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
            <div class="col-md-11 col-lg-9">
                <form method="POST" action="" class="user-subscriptions">
                    {{ csrf_field() }}
                    @include('users/subscriptions_table')
                    <div class="form-group margin-top">    
                        <button type="submit" class="btn btn-primary">
                            {{ __('Save Notifications') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    notificationsInit();
@endsection
