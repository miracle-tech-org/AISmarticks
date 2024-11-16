<?php

namespace Modules\VoipeSmsTickets\Providers;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\CustomerChannel;
use App\Mailbox;
use App\Module;
use App\Thread;
use App\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\File;
use Modules\VoipeIntegration\Providers\VoipeIntegrationServiceProvider;

use Modules\AIAssistant\Jobs\ProcessAIResponse;
use Modules\AIAssistant\Models\Ticket;

class VoipeSmsTicketsServiceProvider extends ServiceProvider
{
    const DRIVER = 'voipesmstickets';
    const CHANNEL = 15;
    const CHANNEL_NAME = 'VoipeSmsTickets';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    public static function listenIncomingMessage()
    {
        // return;

        $this_module = Module::where('alias', 'voipesmstickets')->get()->toArray();
        if ($this_module) if ((int)$this_module[0]['active'] === 0) return;

        $regex = <<<'END'
/
  (
    (?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3 
    ){1,100}                        # ...one or more times
  )
| .                                 # anything else
/x
END;

        // get all mailboxes
        error_log('Start SMS ticket listening');
        $all_mailboxes = Mailbox::all();
        if ($all_mailboxes) foreach ($all_mailboxes as $mailbox) {
            $mailbox_settings = $mailbox->meta[self::DRIVER] ?? [];
            error_log('mailbox_settings = ' . json_encode($mailbox_settings));
            if (!isset($mailbox_settings['last'])) $mailbox_settings['last'] = 0;

            // $client = new \GuzzleHttp\Client();
            // $response = $client->get('https://api.telegram.org/bot902124050:AAF8ZLPKt5Uu3sgguH0e3jNbm2JxUFuZicY/sendMessage?chat_id='.urlencode('504596963').'&text='.urlencode(json_encode($mailbox_settings)));	

            if (@$mailbox_settings['organisation'] != '' && @$mailbox_settings['token'] != '') {
                error_log('http://sms.voipe.co.il/playsms/index.php?app=ws&u=' . urlencode($mailbox_settings['organisation']) . '&h=' . urlencode($mailbox_settings['token']) . '&op=ix&last=' . (int)$mailbox_settings['last']);
                $sresp = file_get_contents('http://sms.voipe.co.il/playsms/index.php?app=ws&u=' . urlencode($mailbox_settings['organisation']) . '&h=' . urlencode($mailbox_settings['token']) . '&op=ix&last=' . (int)$mailbox_settings['last']);
                error_log('sresp=' . json_encode($sresp));
                if ($sresp) {
                    if ($sa = json_decode($sresp, true)) {
                        error_log('sa=' . json_encode($sa));
                        if (isset($sa['data']) && is_array($sa['data'])) {
                            // $ms = 'SMS ids: ';
                            foreach ($sa['data'] as $sms) {
                                error_log('sms = ' . json_encode($sms));
                                if ((int)$mailbox_settings['last'] < (int)$sms['id']) $mailbox_settings['last'] = (int)$sms['id'];
                                // $ms.= $sms['id'].', ';

                                $sms['src'] = preg_replace('/[^01-9]+/uis', '', $sms['src']);
                                $sms['src'] = preg_replace('/^[0]+/uis', '', $sms['src']);
                                if (strlen($sms['src']) >= 8 && strlen($sms['src']) <= 10) $sms['src'] = '972' . $sms['src'];

                                $customer = [
                                    'phone' => $sms['src'] ?? '',
                                    'zip' => '',
                                    'country' => '',
                                    'state' => '',
                                    'city' => '',
                                ];

                                error_log('customer = ' . json_encode($customer));
                                error_log('text = ' . preg_replace($regex, '$1', $sms['msg']));
                                $smsMessage = preg_replace($regex, '$1', $sms['msg']);
                                \VoipeSmsTickets::processIncomingMessage($customer, $smsMessage, $mailbox, []);

                                $ticket = new Ticket;
                                $ticket->message = $smsMessage;
                                ProcessAIResponse::execute($ticket);
                            }
                            // $client = new \GuzzleHttp\Client();
                            // $response = $client->get('https://api.telegram.org/bot902124050:AAF8ZLPKt5Uu3sgguH0e3jNbm2JxUFuZicY/sendMessage?chat_id='.urlencode('504596963').'&text='.urlencode($ms));	
                        }
                    }
                }
            }

            // next code for case, when module's settings were updated
            // while was executed code above
            $actual_mailbox_settings = $mailbox->meta[self::DRIVER] ?? [];
            if (@$actual_mailbox_settings['organisation'] === @$mailbox_settings['organisation'] && @$actual_mailbox_settings['token'] === @$mailbox_settings['token'] && @$actual_mailbox_settings['sender'] === @$mailbox_settings['sender']) {
                $actual_mailbox_settings['last'] = $mailbox_settings['last'];
            }

            $mailbox->setMetaParam(self::DRIVER, $actual_mailbox_settings);
            $mailbox->save();
        }

        // $client = new \GuzzleHttp\Client();
        // $response = $client->get('https://api.telegram.org/bot902124050:AAF8ZLPKt5Uu3sgguH0e3jNbm2JxUFuZicY/sendMessage?chat_id='.urlencode('504596963').'&text='.urlencode($modules[0]['active']));	
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Add item to the mailbox menu
        \Eventy::addAction('mailboxes.settings.menu', function ($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('voipesmstickets::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 35);

        \Eventy::addFilter('menu.selected', function ($menu) {
            $menu['voipesmstickets'] = [
                'mailboxes.voipesmstickets.settings',
            ];
            return $menu;
        });

        \Eventy::addFilter('channel.name', function ($name, $channel) {
            // error_log('sms channel.name '.$name.'|'.$channel);
            if ($name) {
                // error_log('voipe channel.name return '.$name);
                return $name;
            }
            if ($channel == self::CHANNEL) {
                // error_log('voipe channel.name return '.self::CHANNEL_NAME);
                return self::CHANNEL_NAME;
            } else {
                // error_log('voipe channel.name return '.$name);
                return $name;
            }
        }, 20, 2);

        \Eventy::addFilter('channels.list', function ($channels) {
            $channels[self::CHANNEL] = self::CHANNEL_NAME;
            return $channels;
        });

        // \Eventy::addAction('chat_conversation.send_reply', function($conversation, $replies, $customer) {

        // 	// $client = new \GuzzleHttp\Client();
        // 	// $response = $client->get('https://api.telegram.org/bot902124050:AAF8ZLPKt5Uu3sgguH0e3jNbm2JxUFuZicY/sendMessage?chat_id='.urlencode('504596963').'&text=send_reply');	
        // 	// error_log('VoipeSmsTickets send_reply!');

        // 	if ($conversation->channel != self::CHANNEL) {
        //         return;
        //     }

        // 	$mailbox = $conversation->mailbox;

        // 	// channel_id - is a custumer's number
        // 	$channel_id = $customer->getChannelId(self::CHANNEL);

        //     if (!$channel_id) {
        //         error_log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'): customer has no phone number.');
        //         return;
        //     }

        // 	$settings = $mailbox->meta[self::DRIVER] ?? [];
        // 	// error_log('VoipeSmsTickets SETTINGS = '.json_encode($channel_id));

        // 	if (!@$settings['sender']) {
        //         error_log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'). Enter sender in VoipeSmsTickets settings.');
        //         return;
        //     }

        // 	if (!@$settings['organisation']) {
        //         error_log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'). Enter organisation in VoipeSmsTickets settings.');
        //         return;
        //     }

        // 	if (!@$settings['token']) {
        //         error_log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'). Enter token in VoipeSmsTickets settings.');
        //         return;
        //     }

        // 	// We send only the last reply.
        //     $replies = $replies->sortByDesc(function ($item, $key) {
        //         return $item->id;
        //     });
        //     $thread = $replies[0];

        //     // If thread is draft, it means it has been undone
        //     $thread = $thread->fresh();

        //     if ($thread->isDraft()) {
        //         return;
        //     }

        // 	// Attachments.
        //     // $files = [];
        //     // if ($thread->has_attachments) {
        //     //     foreach ($thread->attachments as $attachment) {
        //     //         $files[] = $attachment->url();
        //     //     }
        //     // }

        // 	// if (substr($channel_id,0,3)==='972') $channel_id = '+'.$channel_id;

        // 	$body=json_encode([
        // 		'app'=>'ws',
        // 		'user'=>$settings['organisation'],
        // 		'token'=>$settings['token'],
        // 		'op'=>'pv',
        // 		'to'=>$channel_id,
        // 		'msg'=>$thread->getBodyAsText(),
        // 		'from'=>$settings['sender']
        // 	]);

        // 	// error_log('VoipeSmsTickets reply Body = '.$body);

        // 	$client = new \GuzzleHttp\Client([
        // 		'headers'=>[
        // 			'Content-Length: '.strlen($body),
        // 			'Content-Type'=>'application/json'
        // 		]
        // 	]);

        // 	$response = $client->request('POST', 'https://sms.voipe.co.il/playsms/api.php', ['body' => $body]);
        // 	$statusCode = $response->getStatusCode();
        // 	$content = $response->getBody();
        // 	// error_log('VoipeSmsTickets reply Code = '.$statusCode.', content='.$content);
        // 	// $client = new \GuzzleHttp\Client();
        // 	// $response = $client->get('https://api.telegram.org/bot902124050:AAF8ZLPKt5Uu3sgguH0e3jNbm2JxUFuZicY/sendMessage?chat_id='.urlencode('504596963').'&text='.urlencode(json_encode($content)));	
        // }, 20, 3);

        \Eventy::addAction('conversation.created_by_user', function ($conversation, $thread) {
            $thread = $thread->fresh();
            if ($thread->type != Thread::TYPE_MESSAGE) return;
            $mailbox = $conversation->mailbox;
            $voipe_settings = $mailbox->meta[\VoipeIntegration::DRIVER] ?? [];
            if (isset($voipe_settings['joinconversations'])) {
                return;
            }
            if ($conversation->channel != self::CHANNEL) {
                return;
            }

            $mailbox = $conversation->mailbox;
            $customer = $conversation->customer;
            // channel_id - is a custumer's number
            $channel_id = $customer->getChannelId(self::CHANNEL);

            if (!$channel_id) {
                error_log('Conversation #' . $conversation->number . '. Can not send a reply to the customer (ID ' . $customer->id . '; Phone ' . $customer->getMainPhoneValue() . '): customer has no phone number.');
                return;
            }

            if ($thread->isDraft()) {
                return;
            }

            if (!$this::sendSms($mailbox, $channel_id, $thread->getBodyAsText())) return;
            $thread->setMeta('channel', 'voipesms');
            $thread->save();
        }, 20, 3);

        \Eventy::addAction('conversation.user_forwarded', function ($conversation, $thread, $forwarded_conversation, $forwarded_thread) {
            $thread = $thread->fresh();
            if ($thread->type != Thread::TYPE_MESSAGE) return;
            $mailbox = $conversation->mailbox;
            $voipe_settings = $mailbox->meta[\VoipeIntegration::DRIVER] ?? [];
            if (isset($voipe_settings['joinconversations'])) {
                return;
            }
            if ($conversation->channel != self::CHANNEL) {
                return;
            }

            $mailbox = $conversation->mailbox;
            $customer = $conversation->customer;
            // channel_id - is a custumer's number
            $channel_id = $customer->getChannelId(self::CHANNEL);

            if (!$channel_id) {
                error_log('Conversation #' . $conversation->number . '. Can not send a reply to the customer (ID ' . $customer->id . '; Phone ' . $customer->getMainPhoneValue() . '): customer has no phone number.');
                return;
            }

            if ($thread->isDraft()) {
                return;
            }

            if (!$this::sendSms($mailbox, $channel_id, $thread->getBodyAsText())) return;
            $thread->setMeta('channel', 'voipesms');
            $thread->save();
        }, 20, 3);

        \Eventy::addAction('conversation.user_replied', function ($conversation, $thread) {
            $thread = $thread->fresh();
            if ($thread->type != Thread::TYPE_MESSAGE) return;
            $mailbox = $conversation->mailbox;
            $voipe_settings = $mailbox->meta[\VoipeIntegration::DRIVER] ?? [];
            if (isset($voipe_settings['joinconversations'])) {
                return;
            }
            if ($conversation->channel != self::CHANNEL) {
                return;
            }

            $mailbox = $conversation->mailbox;
            $customer = $conversation->customer;
            // channel_id - is a custumer's number
            $channel_id = $customer->getChannelId(self::CHANNEL);

            if (!$channel_id) {
                error_log('Conversation #' . $conversation->number . '. Can not send a reply to the customer (ID ' . $customer->id . '; Phone ' . $customer->getMainPhoneValue() . '): customer has no phone number.');
                return;
            }

            if ($thread->isDraft()) {
                return;
            }

            if (!$this::sendSms($mailbox, $channel_id, $thread->getBodyAsText())) return;
            $thread->setMeta('channel', 'voipesms');
            $thread->save();
        }, 20, 3);

        \Eventy::addAction('conversation.send_reply_save', function ($conversation, $request) {

            if ($request->is_sms == 1 || $conversation->channel == self::CHANNEL) {
                $conversation->channel = self::CHANNEL;
                $customer = $conversation->customer;
                $mailbox = $conversation->mailbox;
                $channel_id = $request->smsphone;
                $conversation->save();

                //maybe unnecessary
                // if (!$channel_id) $channel_id=$customer->getMainPhoneValue();
                // if (!$channel_id) {
                // 	error_log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'): customer has no phone number.');
                // 	return;
                // }

                // $settings = $mailbox->meta[self::DRIVER] ?? [];
                // // error_log('VoipeSmsTickets SETTINGS = '.json_encode($channel_id));

                // if (!@$settings['sender']) {
                // 	error_log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'). Enter sender in VoipeSmsTickets settings.');
                // 	return;
                // }

                // if (!@$settings['organisation']) {
                // 	error_log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'). Enter organisation in VoipeSmsTickets settings.');
                // 	return;
                // }

                // if (!@$settings['token']) {
                // 	error_log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'). Enter token in VoipeSmsTickets settings.');
                // 	return;
                // }

                // $body=json_encode([
                // 	'app'=>'ws',
                // 	'user'=>$settings['organisation'],
                // 	'token'=>$settings['token'],
                // 	'op'=>'pv',
                // 	'to'=>$channel_id,
                // 	'msg'=>$request->body,
                // 	'from'=>$settings['sender']
                // ]);

                // // error_log('VoipeSmsTickets reply Body = '.$body);

                // $client = new \GuzzleHttp\Client([
                // 	'headers'=>[
                // 		'Content-Length: '.strlen($body),
                // 		'Content-Type'=>'application/json'
                // 	]
                // ]);

                // $response = $client->request('POST', 'https://sms.voipe.co.il/playsms/api.php', ['body' => $body]);
                // $statusCode = $response->getStatusCode();
                // $content = $response->getBody();
                // // error_log('VoipeSmsTickets reply Code = '.$statusCode.', content='.$content);
                // // $client = new \GuzzleHttp\Client();
                // // $response = $client->get('https://api.telegram.org/bot902124050:AAF8ZLPKt5Uu3sgguH0e3jNbm2JxUFuZicY/sendMessage?chat_id='.urlencode('504596963').'&text='.urlencode(json_encode($content)));	
            }
        }, 20, 3);

        \Eventy::addAction('workflows.values_custom', function ($type, $value, $mode, $and_i, $row_i, $data) {
            if ($type != 'voipe_sms') {
                return;
            }
            echo '&nbsp; <a href="' . route('mailboxes.voipesmstickets.ajax_html', ['action' => 'wf_voipe_sms', 'mailbox_id' => $data['mailbox']->id]) . '" class="wf-email-modal" data-modal-title="' . __('Edit SMS') . '" data-modal-no-footer="true" data-modal-on-show="initWfEmailForm">' . __('Customize') . '..</a>
						<input class="form-control" type="hidden" value="' . htmlspecialchars($value) . '" name="' . $mode . '[' . $and_i . '][' . $row_i . '][value]" disabled />';
        }, 20, 6);


        \Eventy::addFilter('workflows.actions_config', function ($actions) {
            $actions['dummy']['items']['voipe_sms'] = [
                'title' => __('Send SMS'),
                'values_custom' => true,
            ];
            return $actions;
        });

        \Eventy::addFilter('workflow.perform_action', function ($performed, $type, $operator, $value, $conversation, $workflow) {
            if ($type == 'voipe_sms') {
                error_log('PERFORMED ' . json_encode($performed));
                error_log('OPERATOR ' . json_encode($operator));
                error_log('VALUE ' . json_encode($value));
                error_log('CONVERSATION ID ' . json_encode($conversation->id));
                error_log('workflow ' . json_encode($workflow));
                error_log('CUSTOMER ' . json_encode($conversation->customer));
                // $value_tags = explode(',', $value);
                // foreach ($value_tags as $tag_name) {
                // 	$tag_name = trim($tag_name);
                // 	Tag::attachByName($tag_name, $conversation->id);
                // }

                try {
                    $value = json_decode($value ?? '', true);
                } catch (\Exception $e) {
                    return true;
                }

                $body = $value['body'] ?? '';

                if ($body) {
                    $mailbox = $conversation->mailbox;
                    $customer = $conversation->customer;
                    $user = User::where('id', $conversation->user_id)->first();
                    if ($value['phone'] == '') {
                        $dst = $customer->getChannelId(self::CHANNEL);
                        if (!$dst) $dst = $customer->getMainPhoneValue();
                    } else {
                        $dst = strip_tags($conversation->replaceTextVars($value['phone'], ['user' => $user]));
                        // $dst = $value['phone'];
                    }
                    if (!$dst) {
                        error_log('Conversation #' . $conversation->number . '. Can not send a reply to the customer (ID ' . $customer->id . '; Phone ' . $customer->getMainPhoneValue() . '): customer has no phone number.');
                        return true;
                    }

                    $user = User::where('id', $conversation->user_id)->first();
                    $value = $conversation->replaceTextVars($body, ['user' => $user]);

                    $this::sendSms($mailbox, $dst, $value);
                    $created_by_user_id = \Workflow::getUser()->id;
                    Thread::createExtended(
                        [
                            'type' => Thread::TYPE_NOTE,
                            'created_by_user_id' => $created_by_user_id,
                            'body' => 'Sent sms to ' . $dst . ': ' . $value,
                            'attachments' => [],
                            'imported' => true,
                        ],
                        $conversation,
                        $customer
                    );
                }
                return true;
            }
            return $performed;
        }, 20, 6);
    }

    public static function sendSms($mailbox, $to, $msg)
    {
        $voipesms_settings = $mailbox->meta[self::DRIVER] ?? [];
        if (!@$voipesms_settings['sender']) {
            error_log('Can not send sms. Enter sender in VoipeSmsTickets settings.');
            return false;
        }

        if (!@$voipesms_settings['organisation']) {
            error_log('Can not send sms. Enter organisation in VoipeSmsTickets settings.');
            return false;
        }

        if (!@$voipesms_settings['token']) {
            error_log('Can not send sms. Enter token in VoipeSmsTickets settings.');
            return false;
        }

        error_log('MSG = ' . $msg);
        $msg = trim(strip_tags(html_entity_decode(preg_replace('/(<br.*?>|<\\/div.*?>|<\\/p.*?>)/uis', "\r\n", $msg))));
        error_log('MSG PREPARED = ' . $msg);
        $body = json_encode([
            'app' => 'ws',
            'user' => $voipesms_settings['organisation'],
            'token' => $voipesms_settings['token'],
            'op' => 'pv',
            'to' => $to,
            'msg' => $msg,
            'from' => $voipesms_settings['sender']
        ]);

        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Content-Length: ' . strlen($body),
                'Content-Type' => 'application/json'
            ]
        ]);

        $response = $client->request('POST', 'https://sms.voipe.co.il/playsms/api.php', ['body' => $body]);
        $statusCode = $response->getStatusCode();
        $content = $response->getBody();
        error_log('VoipeSmsTickets reply Code = ' . $statusCode . ', content=' . $content);
        \DB::insert('insert into billing_statistics (type,month,cnt) values (?, ?, ?) ON DUPLICATE KEY UPDATE `cnt`=`cnt`+?', ["sms_out", date('Y-m'), 1, 1]);
        return true;
    }

    public static function processIncomingMessage($customer_data, $text, $mailbox, $files = [])
    {
        \DB::insert('insert into billing_statistics (type,month,cnt) values (?, ?, ?) ON DUPLICATE KEY UPDATE `cnt`=`cnt`+?', ["sms_in", date('Y-m'), 1, 1]);
        error_log('processIncomingMessage mailbox = ' . json_encode($mailbox));
        // if (in_array($text, self::$skip_messages) && empty($files)) {
        //     return false;
        // }

        // Get or creaate a customer.
        $channel = \VoipeSmsTickets::CHANNEL;
        $channel_id = ltrim($customer_data['phone'], '+');

        $channel_id = preg_replace('/[^01-9]+/uis', '', $channel_id);
        $channel_id = preg_replace('/^[0]+/uis', '', $channel_id);
        if (strlen($channel_id) >= 8 && strlen($channel_id) <= 10) $channel_id = '972' . $channel_id;

        if (!$channel_id) {
            error_log('Empty sender phone number. Check App Logs');
            return;
        }

        $customer = Customer::getCustomerByChannel($channel, $channel_id);

        // Try to find by phone number.
        if (!$customer) {
            // Get first customer by phone number.
            $customer = Customer::findByPhone($channel_id);
            if ($customer) {
                $customer->addChannel($channel, $channel_id);
            }
        }

        if (!$customer) {

            // These two lines will add a record to customer_channel via observer.
            $customer_data['channel'] = $channel;
            $customer_data['channel_id'] = $channel_id;
            $customer_data['first_name'] = $channel_id;
            $customer_data['phones'] = Customer::formatPhones([$channel_id]);

            $customer = Customer::createWithoutEmail($customer_data);

            if (!$customer) {
                error_log('Could not create a customer.');
                return;
            }
        }

        // Get last customer conversation or create a new one.
        $voipe_settings = $mailbox->meta[\VoipeIntegration::DRIVER] ?? [];
        if (isset($voipe_settings['joinconversations'])) {
            $conversation = Conversation::where('mailbox_id', $mailbox->id)
                ->where('customer_id', $customer->id);
            if (!isset($voipe_settings['reopenconversations'])) {
                $conversation = $conversation->where('status', '<>', Conversation::STATUS_CLOSED);
            }
            $conversation = $conversation->orderBy('created_at', 'desc')->first();
        } else {
            $conversation = Conversation::where('mailbox_id', $mailbox->id)
                ->where('customer_id', $customer->id)
                ->where('channel', $channel);
            if (!isset($voipe_settings['reopenconversations'])) {
                $conversation = $conversation->where('status', '<>', Conversation::STATUS_CLOSED);
            }
            $conversation = $conversation->orderBy('created_at', 'desc')->first();
        }

        $attachments = [];

        // if (count($files)) {
        //     foreach ($files as $file) {

        // 		if (!$file['url']) {
        //             continue;
        //         }
        //         $file_url = $file['url'];

        //         // To aboid https://www.twilio.com/docs/api/errors/20003
        //         if (strstr($file_url, '/Accounts/')) {
        //             // MMS.
        //             // https://www.twilio.com/docs/sms/tutorials/how-to-receive-and-download-images-incoming-mms/php-laravel
        //             $driver_config = $mailbox->meta[self::DRIVER] ?? [];
        //             if (!empty($driver_config['sid']) && !empty($driver_config['token'])) {
        //                 $file_url = str_replace('https://', 'https://'.$driver_config['sid'].':'.$driver_config['token'].'@', $file_url);
        //             }
        //             $mms_data = self::getMediaContent($file_url);
        //             if ($mms_data) {
        //                 $attachments[] = [
        //                     'file_name' => \Helper::remoteFileName($file_url).'.'.preg_replace("#.*/#", '', $file['mime_type']),
        //                     'data' => base64_encode($mms_data),
        //                     'mime_type' => $file['mime_type'],
        //                 ];
        //             } else {
        //                 error_log('Conversation #'.$conversation->number.'. Could not retrieve MMS file data: '.$file['url']);
        //             }
        //         } else {
        //             $attachments[] = [
        //                 'file_name' => \Helper::remoteFileName($file_url),
        //                 'file_url' => $file_url,
        //                 'mime_type' => $file['mime_type'],
        //             ];
        //         }
        //     }
        // }

        if (!$text) {
            $text = ' ';
        }

        $text = nl2br($text);

        if ($conversation) {
            $conversation->status = Conversation::STATUS_ACTIVE;
            $conversation->closed_at = null;
            $conversation->closed_by_user_id = null;
            $conversation->updateFolder();
            $conversation->save();
            // Create thread in existing conversation.
            $thread = Thread::createExtended(
                [
                    'type' => Thread::TYPE_CUSTOMER,
                    'customer_id' => $customer->id,
                    'body' => $text,
                    'attachments' => $attachments,
                ],
                $conversation,
                $customer
            );
            $thread->setMeta('channel', 'voipesms');
            $thread->save();
        } else {
            // Create conversation.
            $res = Conversation::create(
                [
                    'type' => Conversation::TYPE_CHAT,
                    'subject' => Conversation::subjectFromText($text),
                    'mailbox_id' => $mailbox->id,
                    'source_type' => Conversation::SOURCE_TYPE_WEB,
                    'channel' => $channel,
                ],
                [[
                    'type' => Thread::TYPE_CUSTOMER,
                    'customer_id' => $customer->id,
                    'body' => $text,
                    'attachments' => $attachments,
                ]],
                $customer
            );
            $res['thread']->setMeta('channel', 'voipesms');
            $res['thread']->save();
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('voipesmstickets.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'voipesmstickets'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/voipesmstickets');

        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/voipesmstickets';
        }, \Config::get('view.paths')), [$sourcePath]), 'voipesmstickets');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
