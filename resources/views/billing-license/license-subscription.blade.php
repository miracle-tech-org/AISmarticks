
<br>
<ul>
    <li>Number of mailboxes are currently allowed: <b>{{ $data['max_mailboxes'] }}</b></li>
    <li>Number of users are currently allowed: @if($data['max_admin'])<span class="fs-tag"><b>admins:</b> {{ $data['max_admin'] }}</span>@endif @if($data['max_user'])<span class="fs-tag"><b>users:</b> {{ $data['max_user'] }}</span> @endif</li>
    <li>Number of set mailboxes: <b>{{ $data['mailboxes'] }}</b></li>
    <li>Number of users:  @if($data['users']['admin'])<span class="fs-tag"><b>admins:</b> {{ $data['users']['admin'] }}</span>@endif @if($data['users']['user'])<span class="fs-tag"><b>users:</b> {{ $data['users']['user'] }}</span> @endif</li>
    <li>Number of workflows are currently allowed: <b>{{ $data['max_workflows'] }}</b></li>
	<li>Number of set workflows: <b>{{ $data['number_of_set_workflows'] }}</b></li>
	<li>DB size: <span class="fs-tag">{{ number_format($data['db_size']['sum'], 2) }} {{ $data['db_size']['type'] }}</span></li>
    <li>External storage folder size: <span class="fs-tag">{{ $data['folder_size'] }}</span></li>
    <li>Subscription type- monthly/yearly:</li>
    <li>
        1msg channel name and id
        <ul>
            @foreach($data['channels_id'] as $key => $channelsID)
                <li>{{$key}} -  <span class="fs-tag">{{$channelsID}}</span></li>
            @endforeach
        </ul>
    </li>
	<li>
		AI Assistance
		<ul>
			@foreach ($agents as $agent)
				<li>
					{{ $agent->name }} - Monthly Limit: {{ $agent->monthly_limit }} - Daily Limit: {{ $agent->daily_limit }} - Cost per token: {{/*$agent->monthly_usage_limit * */0.002}}
				</li>
			@endforeach
		</ul>
	</li>
</ul>
<h3 class="subheader">{{ __('Monthly report of') }}</h3>

<ul>
    <li>Number of total SMS messages: <b>{{ $data['sms_count'] }}</b></li>
    <li>Number of incoming SMS messages: <b>{{ $data['sms_incoming_count'] }}</b></li>
    <li>Number of outgoing SMS messages: <b>{{ $data['sms_outgoing_count'] }}</b></li>
    <li>Number of total WhatsApp messages: <b>{{ $data['whatsapp_count'] }}</b></li>
    <li>Number of incoming WhatsApp messages:  <b>{{ $data['whatsapp_incoming_count'] }}</b></li>
    <li>Number of outgoing WhatsApp messages: <b>{{ $data['whatsapp_outgoing_count'] }}</b></li>
    <li>Number of outgoing WhatsApp messages initiated by us (Authentication category): <b>{{ $data['whatsapp_outgoing_authentication'] }}</b></li>
    <li>Number of outgoing WhatsApp messages initiated by us (Utility category): <b>{{ $data['whatsapp_outgoing_utility'] }}</b></li>
    <li>Number of outgoing WhatsApp messages initiated by us (Marketing category): <b>{{ $data['whatsapp_outgoing_marketing'] }}</b></li>
    <li>Number of set workflows: <b>{{ $data['number_of_set_workflows'] }}</b></li>
	<li>
		Total Monthly AI Usage: {{(int)$agent->monthly_usage}}
		<ul>
			@foreach ($agents as $agent)
				<li>
					{{ $agent->name }} - Current Usage: {{$agent->monthly_usage}}
				</li>
			@endforeach
		</ul>
	</li>
	<li>
		Total Monthly AI Cost: {{(int)$agent->monthly_usage * 0.002}}
		<ul>
			@foreach ($agents as $agent)
				<li>
					{{ $agent->name }} - Current Cost: {{$agent->monthly_usage * 0.002}}
				</li>
			@endforeach
		</ul>
	</li>
</ul>

<h3 class="subheader">{{ __('Monthly report (prev)') }}</h3>

@foreach ($data['prev_monthes'] as $month=>$mdata)
	<a href="" class="billing_monthheader" month="{{$month}}">{{ $month }}</a> &nbsp; 
@endforeach

@foreach ($data['prev_monthes'] as $month=>$mdata)
	<table class="billing_month month{{ $month }}"><thead><tr>
		<th>Number of total SMS messages: </th>
		<th>Number of incoming SMS messages: </th>
		<th>Number of outgoing SMS messages: </th>
		<th>Number of total WhatsApp messages: </th>
		<th>Number of incoming WhatsApp messages:  </th>
		<th>Number of outgoing WhatsApp messages: </th>
		<th>Number of outgoing WhatsApp messages initiated by us (Authentication category): </th>
		<th>Number of outgoing WhatsApp messages initiated by us (Utility category): </th>
		<th>Number of outgoing WhatsApp messages initiated by us (Marketing category): </th>
		<th>Number of set workflows: </th>
	</tr></thead>
	<tbody><tr>
		<td>{{ $mdata['sms_in'] + $mdata['sms_out'] }}</td>
		<td>{{ $mdata['sms_in'] }}</td>
		<td>{{ $mdata['sms_out'] }}</td>
		<td>{{ $mdata['whatsapp_in'] + $mdata['whatsapp_out'] }}</td>
		<td>{{ $mdata ['whatsapp_in'] }}</td>
		<td>{{ $mdata['whatsapp_out'] }}</td>
		<td>{{ $mdata['wtcatauthentication'] }}</td>
		<td>{{ $mdata['wtcatutility'] }}</td>
		<td>{{ $mdata['wtcatmarketing'] }}</td>
		<td>{{ $mdata['count_workflows'] }}</td>
	</tbody></table>
@endforeach
