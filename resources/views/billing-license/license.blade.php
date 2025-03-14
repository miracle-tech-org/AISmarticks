<form class="form-horizontal margin-top margin-bottom" method="POST" action="./license">
    {{ csrf_field() }}

	<div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
        <label for="email" class="col-sm-4 control-label">{{ __('Email for reports') }}</label>

        <div class="col-sm-6">
            <input id="email" type="text" class="form-control input-sized" name="email" value="{{ old('email', $data['email']) }}" />
            @include('partials/field_error', ['field'=>'email'])
        </div>
    </div>
	
    <div class="form-group{{ $errors->has('mailbox') ? ' has-error' : '' }}">
        <label for="mailbox" class="col-sm-4 control-label">{{ __('Inbox limit') }}</label>

        <div class="col-sm-6">
            <input id="mailbox" type="number" class="form-control input-sized" name="mailbox" value="{{ old('mailbox', $data['mailbox']) }}" />
            @include('partials/field_error', ['field'=>'mailbox'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('workflow') ? ' has-error' : '' }}">
        <label for="workflow" class="col-sm-4 control-label">{{ __('Workflows limit') }}</label>

        <div class="col-sm-6">
            <input id="workflow" type="number" class="form-control input-sized" name="workflow" value="{{ old('workflow', $data['workflow']) }}" />
            @include('partials/field_error', ['field'=>'workflow'])
        </div>
    </div>
    <h3 class="subheader">{{ __('Users limits') }}</h3>

    <div class="form-group{{ $errors->has('max_admin') ? ' has-error' : '' }}">
        <label for="max_admin" class="col-sm-4 control-label">{{ __('Max Admins') }}</label>

        <div class="col-sm-6">
            <input id="max_admin" type="number" class="form-control input-sized" name="max_admin" value="{{ old('max_admin', $data['max_admin']) }}" />
            @include('partials/field_error', ['field'=>'max_admin'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('max_user') ? ' has-error' : '' }}">
        <label for="max_user" class="col-sm-4 control-label">{{ __('Max Users') }}</label>

        <div class="col-sm-6">
            <input id="max_user" type="number" class="form-control input-sized" name="max_user" value="{{ old('max_user', $data['max_user']) }}" />
            @include('partials/field_error', ['field'=>'max_user'])
        </div>
    </div>

    <div class="form-group margin-top">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>
</form>
