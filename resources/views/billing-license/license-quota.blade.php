<form class="form-horizontal margin-top margin-bottom" method="POST" action="./license_quota">
    {{ csrf_field() }}

    <div class="form-group{{ $errors->has('sms_out_quota') ? ' has-error' : '' }}">
        <label for="sms_out_quota" class="col-sm-5 control-label">{{ __('quota of outgoing SMS messages') }}</label>

        <div class="col-sm-6">
            <input id="sms_out_quota" type="number" class="form-control input-sized" name="sms_out_quota" value="{{ old('sms_out_quota', $data['sms_out_quota']) }}" />
            @include('partials/field_error', ['field'=>'sms_out_quota'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('whatsapp_out_quota') ? ' has-error' : '' }}">
        <label for="whatsapp_out_quota" class="col-sm-5 control-label">{{ __('quota of outgoing WhatsApp messages') }}</label>

        <div class="col-sm-6">
            <input id="whatsapp_out_quota" type="number" class="form-control input-sized" name="whatsapp_out_quota" value="{{ old('whatsapp_out_quota', $data['whatsapp_out_quota']) }}" />
            @include('partials/field_error', ['field'=>'whatsapp_out_quota'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('db_size_quota') ? ' has-error' : '' }}">
        <label for="db_size_quota" class="col-sm-5 control-label">{{ __('quota of DB size') }}</label>

        <div class="col-sm-6">
            <input id="db_size_quota" type="number" class="form-control input-sized" name="db_size_quota" value="{{ old('db_size_quota', $data['db_size_quota']) }}" />
            @include('partials/field_error', ['field'=>'db_size_quota'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('block_over_quota_sms') ? ' has-error' : '' }}">
        <label for="block_over_quota_sms" class="col-sm-5 control-label">{{ __('block over quota (sms)') }}</label>

        <div class="col-sm-6">
            <input type="hidden" name="block_over_quota_sms" value="0">
            <input id="block_over_quota_sms" type="checkbox" name="block_over_quota_sms" value="1" @if(!empty($data['block_over_quota_sms'])) checked @endif />
            @include('partials/field_error', ['field'=>'block_over_quota_sms'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('block_over_quota_whatsapp') ? ' has-error' : '' }}">
        <label for="block_over_quota_whatsapp" class="col-sm-5 control-label">{{ __('block over quota (whatsapp)') }}</label>

        <div class="col-sm-6">
            <input type="hidden" name="block_over_quota_whatsapp" value="0">
            <input id="block_over_quota_whatsapp" type="checkbox" name="block_over_quota_whatsapp" value="1" @if(!empty($data['block_over_quota_whatsapp'])) checked @endif />
            @include('partials/field_error', ['field'=>'block_over_quota_whatsapp'])
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
