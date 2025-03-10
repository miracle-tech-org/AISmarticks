@extends('layouts.app')

@section('title', __('(no subject)'))
@section('body_class', 'body-conv')
@if (!empty($conversation->id))
    @section('body_attrs')@parent data-conversation_id="{{ $conversation->id }}"@endsection
@endif

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu_view')
@endsection

@section('content')
    @include('partials/flash_messages')

    @php
        if (empty($thread)) {
            $thread = $conversation->threads()->first();
        }
        if (!$thread) {
            $thread = new App\Thread();
        }
    @endphp

    <div id="conv-layout" class="conv-new">
        <div id="conv-layout-header">
            <div id="conv-toolbar">
                
                <div class="conv-actions">
                    <h2>{{ __("New Conversation") }}</h2>

                    <div class="btn-group">
                        <button type="button" class="btn btn-default active conv-switch-button" id="email-conv-switch"><i class="glyphicon glyphicon-envelope"></i></button>
                        <button type="button" class="btn btn-default conv-switch-button" id="phone-conv-switch"><i class="glyphicon glyphicon-earphone"></i></button>
                        <button type="button" class="btn btn-default conv-switch-button" id="sms-conv-switch"><i class="glyphicon glyphicon-comment"></i></button>
                        @if ($whatsappenabled)
							<button type="button" class="btn btn-default conv-switch-button" id="whatsapp-conv-switch"><i class="glyphicon"><svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="20" height="18" viewBox="0 0 48 48" class="pull-left">
								<path fill="#fff" d="M4.868,43.303l2.694-9.835C5.9,30.59,5.026,27.324,5.027,23.979C5.032,13.514,13.548,5,24.014,5c5.079,0.002,9.845,1.979,13.43,5.566c3.584,3.588,5.558,8.356,5.556,13.428c-0.004,10.465-8.522,18.98-18.986,18.98c-0.001,0,0,0,0,0h-0.008c-3.177-0.001-6.3-0.798-9.073-2.311L4.868,43.303z"></path><path fill="#fff" d="M4.868,43.803c-0.132,0-0.26-0.052-0.355-0.148c-0.125-0.127-0.174-0.312-0.127-0.483l2.639-9.636c-1.636-2.906-2.499-6.206-2.497-9.556C4.532,13.238,13.273,4.5,24.014,4.5c5.21,0.002,10.105,2.031,13.784,5.713c3.679,3.683,5.704,8.577,5.702,13.781c-0.004,10.741-8.746,19.48-19.486,19.48c-3.189-0.001-6.344-0.788-9.144-2.277l-9.875,2.589C4.953,43.798,4.911,43.803,4.868,43.803z"></path><path fill="#cfd8dc" d="M24.014,5c5.079,0.002,9.845,1.979,13.43,5.566c3.584,3.588,5.558,8.356,5.556,13.428c-0.004,10.465-8.522,18.98-18.986,18.98h-0.008c-3.177-0.001-6.3-0.798-9.073-2.311L4.868,43.303l2.694-9.835C5.9,30.59,5.026,27.324,5.027,23.979C5.032,13.514,13.548,5,24.014,5 M24.014,42.974C24.014,42.974,24.014,42.974,24.014,42.974C24.014,42.974,24.014,42.974,24.014,42.974 M24.014,42.974C24.014,42.974,24.014,42.974,24.014,42.974C24.014,42.974,24.014,42.974,24.014,42.974 M24.014,4C24.014,4,24.014,4,24.014,4C12.998,4,4.032,12.962,4.027,23.979c-0.001,3.367,0.849,6.685,2.461,9.622l-2.585,9.439c-0.094,0.345,0.002,0.713,0.254,0.967c0.19,0.192,0.447,0.297,0.711,0.297c0.085,0,0.17-0.011,0.254-0.033l9.687-2.54c2.828,1.468,5.998,2.243,9.197,2.244c11.024,0,19.99-8.963,19.995-19.98c0.002-5.339-2.075-10.359-5.848-14.135C34.378,6.083,29.357,4.002,24.014,4L24.014,4z"></path><path fill="#40c351" d="M35.176,12.832c-2.98-2.982-6.941-4.625-11.157-4.626c-8.704,0-15.783,7.076-15.787,15.774c-0.001,2.981,0.833,5.883,2.413,8.396l0.376,0.597l-1.595,5.821l5.973-1.566l0.577,0.342c2.422,1.438,5.2,2.198,8.032,2.199h0.006c8.698,0,15.777-7.077,15.78-15.776C39.795,19.778,38.156,15.814,35.176,12.832z"></path><path fill="#fff" fill-rule="evenodd" d="M19.268,16.045c-0.355-0.79-0.729-0.806-1.068-0.82c-0.277-0.012-0.593-0.011-0.909-0.011c-0.316,0-0.83,0.119-1.265,0.594c-0.435,0.475-1.661,1.622-1.661,3.956c0,2.334,1.7,4.59,1.937,4.906c0.237,0.316,3.282,5.259,8.104,7.161c4.007,1.58,4.823,1.266,5.693,1.187c0.87-0.079,2.807-1.147,3.202-2.255c0.395-1.108,0.395-2.057,0.277-2.255c-0.119-0.198-0.435-0.316-0.909-0.554s-2.807-1.385-3.242-1.543c-0.435-0.158-0.751-0.237-1.068,0.238c-0.316,0.474-1.225,1.543-1.502,1.859c-0.277,0.317-0.554,0.357-1.028,0.119c-0.474-0.238-2.002-0.738-3.815-2.354c-1.41-1.257-2.362-2.81-2.639-3.285c-0.277-0.474-0.03-0.731,0.208-0.968c0.213-0.213,0.474-0.554,0.712-0.831c0.237-0.277,0.316-0.475,0.474-0.791c0.158-0.317,0.079-0.594-0.04-0.831C20.612,19.329,19.69,16.983,19.268,16.045z" clip-rule="evenodd"></path>
							</svg></i></button>
						@endif
						@action('conversation.new.conv_switch_buttons')
                    </div>
                </div>

                <div class="conv-info">
                    #@if ($conversation->number)<strong>{{ $conversation->number }}</strong>@else<strong class="conv-new-number">{{ __("Pending") }}@endif</strong>
                </div>

                <div class="clearfix"></div>

            </div>
        </div>
        <div id="conv-layout-customer">
            @include('conversations/partials/customer_sidebar')
            @action('conversation.new.customer_sidebar', $conversation, $mailbox)
        </div>
        <div id="conv-layout-main" class="conv-new-form">
            <div class="conv-block">
                <div class="row">
                    <div class="col-xs-12">
                        <form class="form-horizontal margin-top form-reply" method="POST" action="" id="form-create">
                            {{ csrf_field() }}
                            <input type="hidden" name="conversation_id" value="{{ $conversation->id }}"/>
                            <input type="hidden" name="mailbox_id" value="{{ $mailbox->id }}"/>
                            {{-- For phone conversation --}}
                            <input type="hidden" name="is_note" value="{{ ($conversation->type == App\Conversation::TYPE_PHONE ? '1' : '') }}"/>
                            <input type="hidden" name="is_phone" value="{{ ($conversation->type == App\Conversation::TYPE_PHONE ? '1' : '') }}"/>
                            <input type="hidden" name="type" value="{{ $conversation->type }}"/>
                            {{-- For sms conversation --}}
                            <input type="hidden" name="is_sms" value=""/>
							{{-- For whatsapp conversation --}}
                            <input type="hidden" name="is_whatsapp" value=""/>
                           	{{-- For drafts --}}
                            <input type="hidden" name="thread_id" value="{{ $thread->id }}"/>
                            {{-- Customer ID is needed not to create empty customers when creating a new phone conversations --}}
                            <input type="hidden" name="customer_id" value="{{ $conversation->customer_id }}"/>
                            <input type="hidden" name="is_create" value="1"/>
                            
                            @if ($conversation->created_by_user_id)
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">{{ __('Author') }}</label>

                                    <div class="col-sm-9">
                                        <label class="control-label text-help">
                                            <i class="glyphicon glyphicon-user"></i> {{ $conversation->created_by_user->getFullName() }}
                                        </label>
                                    </div>
                                </div>
                            @endif

                            <div class="form-group phone-conv-fields{{ $errors->has('name') ? ' has-error' : '' }}">
                                <label for="name" class="col-sm-2 control-label">{{ __('Customer Name') }}</label>

                                <div class="col-sm-9">

                                    <select class="form-control parsley-exclude draft-changer" name="name" id="name" multiple required autofocus/>
                                        @if (!empty($name))
                                            {{-- We use customer ID here because customer may have no emails --}}
                                            @foreach ($name as $name_customer_id => $name_customer_name)
                                                <option value="{{ $name_customer_id }}" selected="selected">{{ $name_customer_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>

                                    @include('partials/field_error', ['field'=>'name'])
                                </div>
                            </div>

							<div class="form-group sms-conv-fields{{ $errors->has('smsphone') ? ' has-error' : '' }}">
                                <label for="smsphone" class="col-sm-2 control-label">{{ __('Phone') }}</label>

                                <div class="col-sm-9">

                                    <input type="text" class="form-control draft-changer" name="smsphone" id="smsphone" />

                                    @include('partials/field_error', ['field'=>'smsphone'])
                                </div>
                            </div>

							<div class="form-group whatsapp-conv-fields{{ $errors->has('whatsappphone') ? ' has-error' : '' }}">
								<label for="whatsappphone" class="col-sm-2 control-label">{{ __('Phone') }}</label>

								<div class="col-sm-9">

									<input type="text" class="form-control draft-changer" name="whatsappphone" id="whatsappphone" />

									@include('partials/field_error', ['field'=>'whatsappphone'])
								</div>
							</div>

							<div class="form-group whatsapp-templates-conv-fields{{ $errors->has('whatsapptemplate') ? ' has-error' : '' }}">
								<label for="whatsapptemplate" class="col-sm-2 control-label">{{ __('Template') }}</label>
								<div class="col-sm-9">
									<select class="form-control" name="whatsapptemplate" id="whatsapptemplate">
										@if(isset($whatsapp_templates['templates']))
											@foreach ($whatsapp_templates['templates'] as $template)
												<option value="{{ $template['id'] }}" >{{ $template['name'] }}</option>
											@endforeach
										@endif
									</select>
									<div id="wt-form"></div>
									@include('partials/field_error', ['field'=>'whatsapptemplate'])
								</div>
							</div>

                            <div class="form-group phone-conv-fields{{ $errors->has('phone') ? ' has-error' : '' }}">
                                <label for="phone" class="col-sm-2 control-label">{{ __('Phone') }}</label>

                                <div class="col-sm-9">

                                    <select class="form-control draft-changer" name="phone" id="phone" placeholder="{{ __('(optional)') }}" multiple/>
                                        @if (!empty($phone))
                                            <option value="{{ $phone }}" selected="selected">{{ $phone }}</option>
                                        @endif
                                    </select>

                                    @include('partials/field_error', ['field'=>'phone'])
                                </div>
                            </div>

                            <div id="conv-to-email-group">
                                <div class="form-group phone-conv-fields{{ $errors->has('to_email') ? ' has-error' : '' }}" id="field-to_email">
                                    <label for="to_email" class="col-sm-2 control-label">{{ __('Email') }}</label>

                                    <div class="col-sm-9">

                                        <select class="form-control draft-changer" name="to_email" id="to_email" placeholder="{{ __('(optional)') }}" multiple/>
                                            @if (!empty($to_email))
                                                @foreach ($to_email as $email => $name)
                                                    <option value="{{ $email }}" selected="selected">{{ $name }}</option>
                                                @endforeach
                                            @endif
                                        </select>

                                        @include('partials/field_error', ['field'=>'to'])
                                    </div>
                                </div>

                                <div class="col-sm-9 col-sm-offset-2 toggle-field phone-conv-fields" id="toggle-email">
                                    <a href="#">{{ __('Add Email') }}</a>
                                </div>
                            </div>

                            @if (count($from_aliases))
                                <div class="form-group email-conv-fields">
                                    <label class="col-sm-2 control-label">{{ __('From') }}</label>

                                    <div class="col-sm-9">
                                        <select name="from_alias" class="form-control">
                                            @foreach ($from_aliases as $from_alias_email => $from_alias_name)
                                                <option value="@if ($from_alias_email != $mailbox->email){{ $from_alias_email }}@endif" @if (!empty($from_alias) && $from_alias == $from_alias_email)selected="selected"@endif>@if ($from_alias_name){{ $from_alias_email }} ({{ $from_alias_name }})@else{{ $from_alias_email }}@endif</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif

                            <div class="form-group{{ $errors->has('to') ? ' has-error' : '' }}" id="field-to">
                                <label for="to" class="col-sm-2 control-label">{{ __('To') }}</label>

                                <div class="col-sm-9">

                                    <select class="form-control recipient-select" name="to[]" id="to" multiple required autofocus/>
                                        @if ($to)
                                            @foreach ($to as $email => $name)
                                                <option value="{{ $email }}" selected="selected">{{ $name }}</option>
                                            @endforeach
                                        @endif
                                    </select>

                                    <label class="checkbox @if (count($to) <= 1) hidden @endif" for="multiple_conversations" id="multiple-conversations-wrap">
                                        <input type="checkbox" name="multiple_conversations" value="1" id="multiple_conversations"> {{ __('Send emails separately to each recipient') }}
                                    </label>

                                    @include('partials/field_error', ['field'=>'to'])
                                </div>
                            </div>

                            <div class="form-group email-conv-fields{{ $errors->has('cc') ? ' has-error' : '' }} @if (!$conversation->cc) hidden @endif field-cc">
                                <label for="cc" class="col-sm-2 control-label">{{ __('Cc') }}</label>

                                <div class="col-sm-9">
                                    <select class="form-control recipient-select" name="cc[]" id="cc" multiple/>
                                        @if ($conversation->getCcArray())
                                            @foreach ($conversation->getCcArray() as $cc)
                                                <option value="{{ $cc }}" selected="selected">{{ $cc }}</option>
                                            @endforeach
                                        @endif
                                    </select>

                                    @include('partials/field_error', ['field'=>'cc'])
                                </div>
                            </div>

                            <div class="form-group email-conv-fields{{ $errors->has('bcc') ? ' has-error' : '' }} @if (!$conversation->bcc) hidden @endif field-cc">
                                <label for="bcc" class="col-sm-2 control-label">{{ __('Bcc') }}</label>

                                <div class="col-sm-9">

                                    <select class="form-control recipient-select" name="bcc[]" id="bcc" multiple/>
                                        @if ($conversation->getBccArray())
                                            @foreach ($conversation->getBccArray() as $bcc)
                                                <option value="{{ $bcc }}" selected="selected">{{ $bcc }}</option>
                                            @endforeach
                                        @endif
                                    </select>

                                    @include('partials/field_error', ['field'=>'bcc'])
                                </div>
                            </div>

                            <div class="col-sm-9 col-sm-offset-2 email-conv-fields toggle-field @if ($conversation->cc && $conversation->bcc) hidden @endif">
                                <a href="#" class="help-link" id="toggle-cc">Cc/Bcc</a>
                            </div>

                            @action('conversation.create_form.before_subject', $conversation, $mailbox, $thread)
                            <div class="form-group subject-conv-fields{{ $errors->has('subject') ? ' has-error' : '' }}">
                                <label for="subject" class="col-sm-2 control-label">{{ __('Subject') }}</label>

                                <div class="col-sm-9">
                                    <input id="subject" type="text" class="form-control" name="subject" value="{{ old('subject', $conversation->subject) }}" maxlength="998" required autofocus>@action('conversation.create_form.subject_append')
                                    @include('partials/field_error', ['field'=>'subject'])
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="tags" class="col-sm-2 control-label" z>{{ __('Tags') }}</label>
                                <div class="col-sm-9">
                                    <select class="form-control" id="tags" name="tags[]" required multiple>
                                        @foreach ($tags as $tag)
                                            <option>{{$tag}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @action('conversation.create_form.after_subject', $conversation, $mailbox, $thread)
                            @php
                                if (!isset($attachments)) {
                                    //$attachments = $conversation->getAttachments();
                                    $attachments = [];
                                }
                            @endphp
                            <div class="thread-attachments attachments-upload" @if (count($attachments)) style="display: block" @endif>
                                @foreach ($attachments as $attachment)
                                    <input type="hidden" name="attachments_all[]" value="{{ $attachment->id }}">
                                    <input type="hidden" name="attachments[]" value="{{ $attachment->id }}" class="atachment-upload-{{ $attachment->id }}">
                                @endforeach
                                <ul>
                                    @foreach ($attachments as $attachment)
                                        <li class="atachment-upload-{{ $attachment->id }} attachment-loaded">
                                            <img src="{{ asset('img/loader-tiny.gif') }}" width="16" height="16"> <a href="{{ $attachment->url() }}" class="break-words" target="_blank">{{ $attachment->file_name }}<span class="ellipsis">…</span> </a> <span class="text-help">({{ $attachment->getSizeName() }})</span> <i class="glyphicon glyphicon-remove" data-attachment-id="{{ $attachment->id }}"></i>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            
                            <div class="form-group{{ $errors->has('body') ? ' has-error' : '' }} conv-reply-body">
                                <div class="col-sm-12">
                                    <textarea id="body" class="form-control" name="body" rows="13" data-parsley-required="true" data-parsley-required-message="{{ __('Please enter a message') }}">{{ old('body', $thread->body) }}</textarea>
                                    <div class="help-block">
                                        @include('partials/field_error', ['field'=>'body'])
                                    </div>
                                </div>
                            </div>
                            
                            
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('conversations/editor_bottom_toolbar', ['new_converstion' => true])
    @action('new_conversation_form.after', $conversation)
@endsection

@include('partials/editor')

@section('javascript')
    @parent
    initReplyForm(true, true, true);
    initNewConversation(@if ($conversation->type == App\Conversation::TYPE_PHONE){{ 'true' }}@endif);
@endsection
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    window.onload = function() {
        $('#tags').select2({
            placeholder: "Select Tags",
            allowClear: true,
            tags: true, // Enables adding new options
        });
    }
</script>
