<?php

namespace Modules\Whapi\Http\Controllers;

use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use App\User;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Storage;
use App\Subscription;

use Modules\AIAssistant\Jobs\ProcessAIResponse;
use Modules\AIAssistant\Models\Ticket;

class WhapiController extends Controller
{
	public function webhooks(Request $request, $mailbox_id, $mailbox_secret)
	{
		if (class_exists('Debugbar')) {
			\Debugbar::disable();
		}

		$mailbox = Mailbox::find($mailbox_id);

		if (!$mailbox) {
			error_log('Incorrect webhook URL: ' . url()->current());
			abort(404);
		}

		$settings = $mailbox->meta[\Whapi::DRIVER] ?? [];

		$token_found = false;
		$channel_name = false;

		error_log('Compare ' . $request->mailbox_secret . ' AND ' . \Whapi::getMailboxSecret($mailbox_id . '_' . $settings['token']) . '(' . $mailbox_id . '_' . $settings['token'] . ')');
		if (\Whapi::getMailboxSecret($mailbox_id . '_' . $settings['token']) == $request->mailbox_secret) {
			$token_found = true;
			$token = $settings['token'];
			$channel_name = $settings['channel_name'];
		}
		if (!$token_found) if (isset($settings['tokens'])) foreach ($settings['tokens'] as $tk => $tv) {
			error_log('Compare ' . $request->mailbox_secret . ' AND ' . \Whapi::getMailboxSecret($mailbox_id . '_' . $tv) . '(' . $mailbox_id . '_' . $tv . ')');
			if (\Whapi::getMailboxSecret($mailbox_id . '_' . $tv) == $request->mailbox_secret) {
				$token_found = true;
				$token = $tv;
				$channel_name = $settings['channels_names'][$tk];
			}
		}
		if (!$token_found) {
			error_log('Incorrect webhook URL: ' . url()->current());
			abort(404);
		}

		if (empty($settings['enabled'])) {
			error_log('Webhook triggered but Whapi integration is not enabled.');
			abort(404);
		}

		$data = $request->all();
		error_log('Whapi IN Data = ' . json_encode($data));
		error_log('Whapi IN Token = ' . json_encode($token));
		error_log('Whapi IN Channel name = ' . json_encode($channel_name));

		if (@$data['event']['type'] == 'statuses') {
			foreach ($data['statuses'] as $a) {
				$thread = Thread::where('meta', 'like', '%' . $a['id'] . '%')->first();
				if ($thread) {
					// const STATUS_ACTIVE = 1;
					// const STATUS_PENDING = 2;
					// const STATUS_CLOSED = 3;
					// const STATUS_SPAM = 4;
					// const STATUS_NOCHANGE = 6;
					// self::STATUS_ACTIVE   => 'active',
					// self::STATUS_CLOSED   => 'closed',
					// self::STATUS_NOCHANGE => 'nochange',
					// self::STATUS_PENDING  => 'pending',
					// self::STATUS_SPAM     => 'spam',
					$thread->setMeta('whapistatus', $a['status']);
					$thread->save();
				}
			}
		} else if (@$data['event']['type'] == 'messages') {
			//\Log::error('Webhook data: '.json_encode($data));
			// error_log('Whapi data: '.json_encode($data));

			foreach ($data['messages'] as $message) {

				$sender_phone = $message['from'] ?? '';
				$sender_phone = preg_replace("/@.*/", '', $sender_phone);
				$sender_name = $message['from_name'] ?? '';
				$channel_id = $message['chat_id'] ?? '';

				$from_customer = true;
				$customer_phone = $message['chat_id'] ?? '';
				$customer_phone = preg_replace("/@.*/", '', $customer_phone);
				$user = null;

				if ($message['from_me']) {
					// Message from user.
					$from_customer = false;

					if (!$customer_phone) {
						error_log('Could not import your own message sent directly from Whapi on mobile phone as a response to customer. Customer phone number which should be passed to Webhook in chatId parameter is empty. Webhook data: ' . json_encode($data));
						return;
					}

					// Try to find user by phone number.
					$user = User::where('phone', $sender_phone)->first();
					if (!$user) {
						error_log('Could not import your own message sent as a response to a customer. Could not find the user with the following phone number: ' . $sender_phone . '. Make sure to set this phone number to one of the users in FreeScout. Webhook data: ' . json_encode($data));
						return;
					}
				}

				$attachments = [];
				$body = '';

				switch ($message['type']) {
					case 'document':
						if (empty($message['document']['caption'])) {
							switch ($message['document']['mime_type']) {
								case 'application/pdf':
									$body = __('Document');
									break;
								case 'image/jpeg':
								case 'image/png':
									$body = __('Image');
									break;
								case 'video/3gp':
								case 'video/mp4':
									$body = __('Video');
									break;
								case 'audio/aac':
								case 'audio/amr':
								case 'audio/mpeg':
								case 'audio/mp4':
								case 'audio/ogg':
									__('Audio');
									break;
							}
						} else {
							$body = $message['document']['caption'];
						}

						$fname = @$message['document']['file_name'];
						if ($fname == '') {
							$fname = 'document.pdf';
							switch ($message['document']['mime_type']) {
								case 'application/pdf':
									$fname = 'document.pdf';
									break;
								case 'image/jpeg':
									$fname = 'document.jpg';
									break;
								case 'image/png':
									$fname = 'document.png';
									break;
								case 'video/3gp':
									$fname = 'document.3gp';
									break;
								case 'video/mp4':
									$fname = 'document.mp4';
									break;
								case 'audio/aac':
									$fname = 'document.aac';
									break;
								case 'audio/amr':
									$fname = 'document.am3';
									break;
								case 'audio/mpeg':
									$fname = 'document.mpeg';
									break;
								case 'audio/mp4':
									$fname = 'document.mp4';
									break;
								case 'audio/ogg':
									$fname = 'document.ogg';
									break;
							}
						}

						if (@$message['document']['link'] == '') {
							$fcontents = \Whapi::getMedia($message['document']['id'], $token);
							$attachments[] = [
								'file_name' => $fname,
								'data' => base64_encode($fcontents),
							];
						} else {
							$attachments[] = [
								'file_name' => $fname,
								'file_url' => $message['document']['link'],
							];
						}
						break;

					case 'image':
						if (empty($message['image']['caption'])) {
							$body = __('Image');
						} else {
							$body = $message['image']['caption'];
						}
						$fcontents = \Whapi::getMedia($message['image']['id'], $token);
						$fname = 'image.jpg';
						switch ($message['image']['mime_type']) {
							case 'image/png':
								$fname = 'image.png';
								break;
						}
						$attachments[] = [
							'file_name' => $fname,
							'data' => base64_encode($fcontents),
						];
						error_log('ATTACHMENTS = ' . json_encode($attachments));
						break;

					case 'video':
						if (empty($message['video']['caption'])) {
							$body = __('Video');
						} else {
							$body = $message['video']['caption'];
						}
						$fcontents = \Whapi::getMedia($message['video']['id'], $token);
						$fname = 'video.mp4';
						switch ($message['video']['mime_type']) {
							case 'video/mp4':
								$fname = 'video.mp4';
								break;
							case 'video/3gp':
								$fname = 'video.3gp';
								break;
						}
						$attachments[] = [
							'file_name' => $fname,
							'data' => base64_encode($fcontents),
						];
						error_log('ATTACHMENTS = ' . json_encode($attachments));
						break;

					case 'location':
						$body = __('Location') . ': <img src="' . $message['location']['preview'] . '"/>';
						break;

					case 'voice':
						$body = __('Audio');
						$attachments[] = [
							'file_name' => 'audio',
							'file_url' => (@$message['voice']['link'] == '') ? 'https://gate.whapi.cloud/media/' . $message['voice']['id'] : $message['voice']['link'],
						];
						break;

					case 'contact':
						$body = __('Contact') . ' ' . $message['contact']['name'];
						$vlines = explode("\n", $message['contact']['vcard']);
						foreach ($vlines as $line) {
							$line = trim($line);
							if (!in_array($line, ['BEGIN:VCARD', 'VERSION:3.0', 'END:VCARD'])) {
								$va = explode(':', $line);
								if (isset($va[1])) {
									$body .= "\n" . $va[0] . ': ' . $va[1];
								}
							}
						}
						break;

					case 'contact_list':
						$body = '';
						foreach ($message['contact_list'] as $contact) {
							if ($body != '') $body .= "\n\n";
							$body .= __('Contact') . ' ' . $contact['name'];
							$vlines = explode("\n", $contact['vcard']);
							foreach ($vlines as $line) {
								$line = trim($line);
								if (!in_array($line, ['BEGIN:VCARD', 'VERSION:3.0', 'END:VCARD'])) {
									$va = explode(':', $line);
									if (isset($va[1])) {
										$body .= "\n" . $va[0] . ': ' . $va[1];
									}
								}
							}
						}
						break;

					case 'link_preview':
						$body = $message['link_preview']['title'] . ': ' . $message['link_preview']['url'];
						break;

					case 'text':
						$body = $message['text']['body'];
						break;

					default:
						$body = 'Unsupported message type: ' . $message['type'];
						error_log('Unsupported Whapi message type ' . $message['type']);
				}
				$body = htmlspecialchars($body);
				$body = nl2br($body);

				if ($from_customer) {
					\DB::insert('insert into billing_statistics (type,month,cnt) values (?, ?, ?) ON DUPLICATE KEY UPDATE `cnt`=`cnt`+?', ["whapi_in", date('Y-m'), 1, 1]);
					\Whapi::processIncomingMessage($sender_phone, $sender_name, $channel_id, $body, $mailbox, $attachments, $token, $channel_name);
				} else {
					// From user.
					\Whapi::processOwnMessage($user, $customer_phone, $body, $mailbox, $attachments, $customer_phone);
				}

				$ticket = new Ticket;
				$ticket->message = $body;
				$job = new ProcessAIResponse($ticket);
				$job->execute();
			}
		}
	}

	public function cronMetricsSimulate()
	{
		error_log('Begin cronMetrics simulate');
		$all_mailboxes = Mailbox::all();
		if ($all_mailboxes) foreach ($all_mailboxes as $mailbox) {
			$config = $mailbox->meta[\Whapi::DRIVER] ?? [];
			error_log('cronMetrics simulate ' . $mailbox->id . ' config = ' . json_encode($config));
			if (!empty($config['enabled'])) {
				$conversation = Conversation::where('mailbox_id', $mailbox->id)->orderBy('created_at', 'desc')->first();
				$user = User::where('id', $conversation->user_id)->first();
				$tokens = [];
				$tokens[] = $config['token'] ?? '';
				if (isset($config['tokens'])) foreach ($config['tokens'] as $token) {
					$t = trim($token);
					if ($t != '') $tokens[] = $t;
				}
				foreach ($tokens as $token) if ($token != '') {
					$health = [
						'lifeTime' => 1,
						'riskFactor' => 1,
						'riskFactorChats' => 1,
						'riskFactorContacts' => 1,
					];
					\Whapi::cronMetricsSendNotification($conversation, $health);
				}
			}
		}
		echo 'Notification sent (only to users with corresponding checkbox in page Notifications)';
	}

	/**
	 * Reports.
	 */
	public function reportConversationvolume()
	{
		$data = Conversation::where('channel', \Whapi::CHANNEL)
			->selectRaw('DATE(created_at) as date, COUNT(*) as count')
			->groupBy('date')
			->get();

		return view('whapi::report_conversationvolume', [
			'data'	=> $data,
		]);
	}
	public function reportResponsetime()
	{
		$data = Thread::whereHas('conversation', function ($query) {
			$query->where('channel', \Whapi::CHANNEL);
		})
			->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_response_time')
			->get();

		return view('whapi::report_responsetime', [
			'data'	=> $data,
		]);
	}
	public function reportCustomerengagement()
	{
		$uniqueCustomers = Conversation::where('channel', \Whapi::CHANNEL)
			->distinct('customer_email')
			->count();

		$conversationLengths = Conversation::where('channel', \Whapi::CHANNEL)
			->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_length')
			->get();

		$repeatContacts = Conversation::where('channel', \Whapi::CHANNEL)
			->selectRaw('customer_email, COUNT(*) as contact_count')
			->groupBy('customer_email')
			->havingRaw('contact_count > 1')
			->get();

		$data = [
			'unique_customers' => $uniqueCustomers,
			'avg_conversation_length' => $conversationLengths,
			'repeat_contacts' => $repeatContacts,
		];

		return view('whapi::report_customerengagement', [
			'data'	=> $data,
		]);
	}
	public function reportMessagetype()
	{
		$data = Thread::whereHas('conversation', function ($query) {
			$query->where('channel', \Whapi::CHANNEL);
		})
			->selectRaw('type, COUNT(*) as count')
			->groupBy('type')
			->get();

		return view('whapi::report_messagetype', [
			'data'	=> $data,
		]);
	}
	public function reportContentanalysis()
	{
		$data = Thread::whereHas('conversation', function ($query) {
			$query->where('channel', \Whapi::CHANNEL);
		})
			->selectRaw('body, COUNT(*) as count')
			->groupBy('body')
			->orderBy('count', 'desc')
			->limit(10)
			->get();

		return view('whapi::report_contentanalysis', [
			'data'	=> $data,
		]);
	}
	public function reportAgentperformance()
	{
		$data = Thread::whereHas('conversation', function ($query) {
			$query->where('channel', \Whapi::CHANNEL);
		})
			->selectRaw('user_id, AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_handling_time, COUNT(*) as resolution_count')
			->groupBy('user_id')
			->get();

		return view('whapi::report_agentperformance', [
			'data'	=> $data,
		]);
	}
	public function reportCampaigneffectiveness()
	{
		$openRates = Broadcast::where('channel', 'whatsapp')
			->selectRaw('campaign_id, COUNT(*) as open_count')
			->groupBy('campaign_id')
			->get();

		$responseRates = Broadcast::where('channel', 'whatsapp')
			->selectRaw('campaign_id, COUNT(*) as response_count')
			->groupBy('campaign_id')
			->get();

		$conversionRates = Sale::whereHas('conversation', function ($query) {
			$query->where('channel', \Whapi::CHANNEL);
		})
			->selectRaw('campaign_id, COUNT(*) as conversion_count')
			->groupBy('campaign_id')
			->get();

		$data = [
			'open_rates' => $openRates,
			'response_rates' => responseRates,
			'conversion_rates' => conversionRates,
		];

		return view('whapi::report_campaigneffectiveness', [
			'data'	=> $data,
		]);
	}

	/**
	 * Settings.
	 */
	public function dashboard($mailbox_id)
	{
		$mailbox = Mailbox::findOrFail($mailbox_id);

		if (!auth()->user()->isAdmin()) {
			\Helper::denyAccess();
		}

		$settings = $mailbox->meta[\Whapi::DRIVER] ?? [];
		if (!empty($settings['enabled'])) $health = \Whapi::getMetrics($settings['token'] ?? '');
		// $indicators=[
		// 	'1'=>'<span class="badge badge-danger">Use caution</span>',
		// 	'2'=>'<span class="badge badge-warning">Needs Attention</span>',
		// 	'3'=>'<span class="badge badge-success">Good Indicator</span>',
		// ];
		$health['lifeTime'] = (int)$health['lifeTime'];
		$health['riskFactor'] = (int)$health['riskFactor'];
		$health['riskFactorChats'] = (int)$health['riskFactorChats'];
		$health['riskFactorContacts'] = (int)$health['riskFactorContacts'];

		if (empty($health)) {
			$health = [];
		}

		return view('whapi::dashboard', [
			'mailbox'   => $mailbox,
			'health'	=> $health,
		]);
	}

	public function channels($mailbox_id)
	{
		$mailbox = Mailbox::findOrFail($mailbox_id);

		// if (!auth()->user()->isAdmin()) {
		//     \Helper::denyAccess();
		// }

		$settings = $mailbox->meta[\Whapi::DRIVER] ?? [];
		if (@$settings['token'] != '') {
			$settings['token_settings'] = \Whapi::getHealth($settings['token']);
			if (@$settings['token_settings']['status']['text'] != 'QR') {
				$settings['activated_token'] = $settings['token'];
				unset($settings['token']);
			}
		}
		if (is_array(@$settings['tokens'])) {
			$settings['tokens_settings'] = [];
			foreach ($settings['tokens'] as $tk => $tv) {
				$settings['tokens_settings'][$tk] = [];
				if ($tv != '') $settings['tokens_settings'][$tk] = \Whapi::getHealth($tv);
				if (@$settings['tokens_settings'][$tk]['status']['text'] != 'QR') {
					$settings['activated_tokens'][$tk] = $settings['tokens'][$tk];
					unset($settings['tokens'][$tk]);
				}
			}
		}
		return view('whapi::channels', [
			'mailbox'   => $mailbox,
			'settings' => $settings,
		]);
	}
	public function channelsqr($mailbox_id, $mailbox_secret)
	{
		$mailbox = Mailbox::findOrFail($mailbox_id);

		// if (!auth()->user()->isAdmin()) {
		//     \Helper::denyAccess();
		// }
		$settings = $mailbox->meta[\Whapi::DRIVER] ?? [];

		$token_found = false;
		$channel_name = false;
		if (\Whapi::getMailboxSecret($mailbox_id . '_' . $settings['token']) == $mailbox_secret) {
			$token_found = true;
			$token = $settings['token'];
			$channel_name = $settings['channel_name'];
		}
		if (!$token_found) if (isset($settings['tokens'])) foreach ($settings['tokens'] as $tk => $tv) if (\Whapi::getMailboxSecret($mailbox_id . '_' . $tv) == $mailbox_secret) {
			$token_found = true;
			$token = $tv;
			$channel_name = $settings['channels_names'][$tk];
		}
		if (!$token_found) {
			error_log('Incorrect Qr URL: ' . url()->current());
			abort(404);
		}
		$base64 = \Whapi::getQrBase64($token);

		return view('whapi::channelsqr', [
			'mailbox'   => $mailbox,
			'qr' => $base64
		]);
	}

	public function channelslogout($mailbox_id, $mailbox_secret)
	{
		$mailbox = Mailbox::findOrFail($mailbox_id);

		// if (!auth()->user()->isAdmin()) {
		//     \Helper::denyAccess();
		// }
		$settings = $mailbox->meta[\Whapi::DRIVER] ?? [];

		$token_found = false;
		$channel_name = false;
		if (\Whapi::getMailboxSecret($mailbox_id . '_' . $settings['token']) == $mailbox_secret) {
			$token_found = true;
			$token = $settings['token'];
			$channel_name = $settings['channel_name'];
		}
		if (!$token_found) if (isset($settings['tokens'])) foreach ($settings['tokens'] as $tk => $tv) if (\Whapi::getMailboxSecret($mailbox_id . '_' . $tv) == $mailbox_secret) {
			$token_found = true;
			$token = $tv;
			$channel_name = $settings['channels_names'][$tk];
		}
		if (!$token_found) {
			error_log('Incorrect Qr URL: ' . url()->current());
			abort(404);
		}
		$base64 = \Whapi::tokenLogout($token);
		echo 'Logged out';
	}

	/**
	 * Settings.
	 */
	public function settings($mailbox_id)
	{
		$mailbox = Mailbox::findOrFail($mailbox_id);

		if (!auth()->user()->isAdmin()) {
			\Helper::denyAccess();
		}

		$settings = $mailbox->meta[\Whapi::DRIVER] ?? [];

		return view('whapi::settings', [
			'mailbox'   => $mailbox,
			'settings'   => $settings,
		]);
	}

	/**
	 * Settings save.
	 */
	public function settingsSave(Request $request, $mailbox_id)
	{
		$mailbox = Mailbox::findOrFail($mailbox_id);

		$settings_new = $request->settings;
		$settings = $mailbox->meta[\Whapi::DRIVER] ?? [];

		$settings['enabled'] = (int)($settings_new['enabled'] ?? 0);
		$settings['initiate_enabled'] = (int)($settings_new['initiate_enabled'] ?? 0);
		$settings['token'] = $settings_new['token'];
		if ($settings['token'] != '') \Whapi::setWebhookUrl($mailbox_id, $settings['token']);
		$settings['channel_name'] = $settings_new['channel_name'];
		if (isset($settings_new['tokens'])) {
			$settings['tokens'] = $settings_new['tokens'];
			$settings['channels_names'] = $settings_new['channels_names'];
			if (is_array($settings['tokens'])) foreach ($settings['tokens'] as $token) if ($token != '') {
				\Whapi::setWebhookUrl($mailbox_id, $token);
			}
		} else {
			unset($settings['tokens']);
			unset($settings['channels_names']);
		}
		// Try to add or remove webhook - dont see such possibilities in documentation - so it will be manual I guess

		$mailbox->setMetaParam(\Whapi::DRIVER, $settings);
		$mailbox->save();

		\Session::flash('flash_success_floating', __('Settings updated'));

		return redirect()->route('mailboxes.whapi.settings', ['mailbox_id' => $mailbox_id]);
	}

	/**
	 * Ajax controller.
	 */
	public function ajaxHtml(Request $request)
	{
		$user = auth()->user();

		switch ($request->action) {
			case 'wf_whapi':
				error_log('REQUEST  = ' . json_encode($request->all()));
				if (!\Workflow::canEditWorkflows()) {
					\Helper::denyAccess();
				}
				$value = '';
				$phone = '';
				$text = '';
				$mailbox = Mailbox::findOrFail($request->mailbox_id);
				if (!auth()->user()->can('view', $mailbox)) {
					\Helper::denyAccess();
				}
				$wsettings = $mailbox->meta[\Whapi::DRIVER] ?? [];
				return view('whapi::partials/wf_whapi', [
					'value' => $value,
					'mailbox' => $mailbox,
					'phone' => $phone,
					'text' => $text,
				]);
				break;
			case 'whapi_qr':
				$wsettings = $mailbox->meta[\Whapi::DRIVER] ?? [];
				$base64 = \Whapi::getQrBase64($token);
				return json_encode([
					'qr' => $base64,
				]);
				break;
		}

		abort(404);
	}

	/**
	 * Show the form for creating a new resource.
	 * @return Response
	 */
	public function create()
	{
		return view('whapi::create');
	}

	/**
	 * Store a newly created resource in storage.
	 * @param  Request $request
	 * @return Response
	 */
	public function store(Request $request) {}

	/**
	 * Show the specified resource.
	 * @return Response
	 */
	public function show()
	{
		return view('whapi::show');
	}

	/**
	 * Show the form for editing the specified resource.
	 * @return Response
	 */
	public function edit()
	{
		return view('whapi::edit');
	}

	/**
	 * Update the specified resource in storage.
	 * @param  Request $request
	 * @return Response
	 */
	public function update(Request $request) {}

	/**
	 * Remove the specified resource from storage.
	 * @return Response
	 */
	public function destroy() {}
}
