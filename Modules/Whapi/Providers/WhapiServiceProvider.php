<?php

namespace Modules\Whapi\Providers;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use App\Folder;
use App\Follower;
use App\User;
use App\Subscription;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\Storage;
use App\Notifications\BroadcastNotification;
use App\Notifications\WebsiteNotification;
use Illuminate\Database\Eloquent\Model;

use App\Mail\UserNotificationWhapiHealth;
use App\SendLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;

class WhapiServiceProvider extends ServiceProvider
{
	const DRIVER = 'whapi';
	const CHANNEL = 16;
    const CHANNEL_NAME = 'Whapi';
	const EVENT_NONHEALTH = 16;
	const LOG_NAME = 'whapi_errors';
	const SALT = 'asd@-1!asmart';
	public static $skip_messages = [
        // '%%%_IMAGE_%%%',
        // '%%%_VIDEO_%%%',
        // '%%%_FILE_%%%',
        // '%%%_AUDIO_%%%',
        // '%%%_LOCATION_%%%',
    ];

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

    /**
     * Module hooks.
     */
    public function hooks()
    {
		// Show tags next to the conversation title in conversation
        \Eventy::addAction('conversation.after_subject', function($conversation, $mailbox) {
            $config = $mailbox->meta[\Whapi::DRIVER] ?? [];
			if (isset($conversation->meta['whtoken'])) if ($conversation->meta['whtoken']!='')
			{
				if ($conversation->meta['whtoken']==@$config['token'])
				{
					echo '<span class="conv-tags"><span class="fs-tag fs-tag-md tag-c-'.@$config['color'].'"><a class="fs-tag-name" href="" target="_blank" onClick="javascript:{return false;}">'.@$config['channel_name'].'</a></span></span>';
				}
				else if (isset($config['tokens'])) if (($tk=array_search($conversation->meta['whtoken'],$config['tokens']))!==false)
				{
					echo '<span class="conv-tags"><span class="fs-tag fs-tag-md tag-c-'.@$config['colors'][$tk].'"><a class="fs-tag-name" href="" target="_blank" onClick="javascript:{return false;}">'.@$config['channels_names'][$tk].'</a></span></span>';
				}
			}
		}, 20, 2);

		\Eventy::addAction('conversations_table.before_subject', function($conversation) {
            $config = $conversation->mailbox->meta[\Whapi::DRIVER] ?? [];
			if (isset($conversation->meta['whtoken'])) if ($conversation->meta['whtoken']!='')
			{
				if ($conversation->meta['whtoken']==@$config['token'])
				{
					echo '<span class="fs-tag tag-c-'.@$config['color'].' pull-left crm-tag" data-toggle="tooltip" title="" data-original-title="Channel"><span class="fs-tag-name">'.@$config['channel_name'].'</span></span>';
				}
				else if (isset($config['tokens'])) if (($tk=array_search($conversation->meta['whtoken'],$config['tokens']))!==false)
				{
					echo '<span class="fs-tag tag-c-'.@$config['colors'][$tk].' pull-left crm-tag" data-toggle="tooltip" title="" data-original-title="Channel"><span class="fs-tag-name">'.@$config['channels_names'][$tk].'</span></span>';
				}
			}
		}, 20, 1);

		// // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath('whapi').'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath('whapi').'/js/module.js';
            return $javascripts;
        }, 20, 1);

        // Add item to the mailbox menu
        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('whapi::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 35, 1);

		// Add item to the mailbox menu
        \Eventy::addAction('reports.menu', function($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('whapi::partials/reports_menu', [])->render();
            }
        }, 20, 1);

		\Eventy::addFilter('menu.selected', function($menu) {
            $menu['whapi'] = [
                'mailboxes.whapi.settings',
            ];
			$menu['whapi_dashboard'] = [
                'mailboxes.whapi.dashboard',
            ];
			$menu['whapi_channels'] = [
                'mailboxes.whapi.channels',
            ];
			$menu['reports'] = [
				'whapi.report_conversationvolume',
			];
            return $menu;
        }, 20, 1);

		\Eventy::addFilter('channel.name', function($name, $channel) {
            if ($name) {
                return $name;
            }
            if ($channel == self::CHANNEL) {
                return self::CHANNEL_NAME;
            } else {
                return $name;
            }
        }, 20, 2);

		\Eventy::addFilter('channels.list', function($channels) {
            $channels[self::CHANNEL] = self::CHANNEL_NAME;
            return $channels;
        }, 20, 1);

		\Eventy::addAction('conversation.created_by_user', function($conversation, $thread) {
		    $thread = $thread->fresh();
            if ($thread->type!=Thread::TYPE_MESSAGE) return;
			if ($conversation->channel != self::CHANNEL) {
                return;
            }
			$mailbox = $conversation->mailbox;
			$voipe_settings = $mailbox->meta[\VoipeIntegration::DRIVER] ?? [];
			if (isset($voipe_settings['joinconversations'])) return;

            $customer=$conversation->customer;
			$channel_id = $customer->getChannelId(self::CHANNEL);

            if (!$channel_id) {
				\Whapi::log('Can not send a reply to the customer ('.$customer->id.': '.$customer->getFullName().'): customer has no messenger ID.', $conversation->mailbox, true);
				error_log('Can not send a reply to the customer ('.$customer->id.': '.$customer->getFullName().'): customer has no messenger ID.');
                return;
            }

            if ($thread->isDraft() || $thread->imported) {
                return;
            }

            $text = $thread->getBodyAsText();

            $config = $conversation->mailbox->meta[\Whapi::DRIVER] ?? [];

			//if session is open
			$wsent=false;
			if (isset($customer->meta['whapilastdate'])) if (microtime(true)-$customer->meta['whapilastdate']<86000)
			{
				$wsent=true;
				$token=$config['token']??'';
				if (isset($conversation->meta['whtoken'])) if (isset($config['tokens'])) if (in_array($conversation->meta['whtoken'],$config['tokens'])) $token=$conversation->meta['whtoken'];
				$conversation->setMeta('whtoken',$token);
				$conversation->save();
				$response=self::sendMessage($channel_id,$text,$token,$thread->has_attachments?$thread->attachments:[]);
				error_log('Whapi response = '.json_encode($response));
				if (empty($response['sent'])) {
					\Whapi::log('Error occurred sending Whapi message to '.$channel_id, $conversation->mailbox, true);
					error_log('Error occurred sending Whapi message to '.$channel_id.': '.json_encode($response));
				}
				if (isset($response['message']['id']))
				{
					$thread->setMeta('whapiid',$response['message']['id']);
					$thread->save();
				}
			}
			$thread->setMeta('channel','whapi');
			$thread->save();
			
        }, 20, 2);

		\Eventy::addFilter('voipeintegration.senddefault', function($result, $conversation, $thread, $default_channel) {
		    error_log('voipeintegration.senddefault whapi');
			error_log('voipeintegration.senddefault whapi in '.json_encode($result));
			if (strpos($default_channel,self::CHANNEL.'_')===0)
			{
				error_log('Lets use whapi (default)!');
				$thread = $thread->fresh();
				if ($thread->type!=Thread::TYPE_MESSAGE) return;
				$customer=$conversation->customer;
				$channel_id = $customer->getChannelId(self::CHANNEL);
				if (!$channel_id) $channel_id=$customer->getChannelId(\VoipeIntegration::CHANNEL);
				if (!$channel_id) $channel_id=$customer->getChannelId(\VoipeSmsTickets::CHANNEL);
				if (!$channel_id) {
					\Whapi::log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'): customer has no phone number.', $conversation->mailbox, true);
					error_log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'): customer has no phone number.');
					return $result;
				}

				if ($thread->isDraft() || $thread->imported) {
					return $result;
				}

				$text = $thread->getBodyAsText();

				$config = $conversation->mailbox->meta[\Whapi::DRIVER] ?? [];

				$token=$config['token']??'';
				$tk=preg_replace('/^.+_/uis','',$default_channel);
				if (isset($config['tokens'][$tk-1])) $token=$config['tokens'][$tk-1];
				error_log('Default channel '.$default_channel.' = '.$token);
				$response=self::sendMessage($channel_id,$text,$token,$thread->has_attachments?$thread->attachments:[]);
				error_log('Whapi response = '.json_encode($response));
				if (empty($response['sent'])) {
					\Whapi::log('Error occurred sending Whapi message to '.$channel_id, $conversation->mailbox, true);
					error_log('Error occurred sending Whapi message to '.$channel_id.': '.json_encode($response));
				}
				if (isset($response['message']['id']))
				{
					$thread->setMeta('whapiid',$response['message']['id']);
					$thread->save();
				}
				$thread->setMeta('channel','whapi');
				$thread->save();
				$result['whapi']=true;
			}
			error_log('voipeintegration.senddefault whapi out '.json_encode($result));
			return $result;
        }, 20, 4);

		\Eventy::addFilter('voipeintegration.sendwhapi', function($result, $conversation, $thread) {
		    $thread = $thread->fresh();
            if ($thread->type!=Thread::TYPE_MESSAGE) return;
			$customer=$conversation->customer;
			$channel_id = $customer->getChannelId(self::CHANNEL);
            if (!$channel_id) $channel_id=$customer->getChannelId(\VoipeIntegration::CHANNEL);
			if (!$channel_id) $channel_id=$customer->getChannelId(\VoipeSmsTickets::CHANNEL);
			if (!$channel_id) {
                \Whapi::log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'): customer has no phone number.', $conversation->mailbox, true);
				error_log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'): customer has no phone number.');
                return false;
            }

            if ($thread->isDraft() || $thread->imported) {
                return false;
            }

            $text = $thread->getBodyAsText();

            $config = $conversation->mailbox->meta[\Whapi::DRIVER] ?? [];

			$token=$config['token']??'';
			if (isset($conversation->meta['whtoken'])) if (isset($config['tokens'])) if (in_array($conversation->meta['whtoken'],$config['tokens'])) $token=$conversation->meta['whtoken'];
			$conversation->setMeta('whtoken',$token);
			$conversation->save();
			$response=self::sendMessage($channel_id,$text,$token,$thread->has_attachments?$thread->attachments:[]);
			error_log('Whapi response = '.json_encode($response));
			if (empty($response['sent'])) {
				\Whapi::log('Error occurred sending Whapi message to '.$channel_id, $conversation->mailbox, true);
				error_log('Error occurred sending Whapi message to '.$channel_id.': '.json_encode($response));
			}
			if (isset($response['message']['id']))
			{
				$thread->setMeta('whapiid',$response['message']['id']);
				$thread->save();
			}
			$thread->setMeta('channel','whapi');
			$thread->save();
			return true;
		
        }, 20, 3);

		\Eventy::addAction('conversation.user_forwarded', function($conversation, $thread, $forwarded_conversation, $forwarded_thread) {
			$thread = $thread->fresh();
            if ($thread->type!=Thread::TYPE_MESSAGE) return;
			if ($conversation->channel != self::CHANNEL) {
                return;
            }
			$mailbox = $conversation->mailbox;
			$voipe_settings = $mailbox->meta[\VoipeIntegration::DRIVER] ?? [];
			if (isset($voipe_settings['joinconversations'])) return;

            $customer=$conversation->customer;
			$channel_id = $customer->getChannelId(self::CHANNEL);

            if (!$channel_id) {
                \Whapi::log('Can not send a reply to the customer', $conversation->mailbox, true);
				error_log('Can not send a reply to the customer ('.$customer->id.': '.$customer->getFullName().'): customer has no messenger ID.');
                return;
            }

            if ($thread->isDraft() || $thread->imported) {
                return;
            }

            $text = $thread->getBodyAsText();

            $config = $conversation->mailbox->meta[\Whapi::DRIVER] ?? [];
			
			//if session is open
			$wsent=false;
			if (isset($customer->meta['whapilastdate'])) if (microtime(true)-$customer->meta['whapilastdate']<86000)
			{
				$wsent=true;
				$token=$config['token']??'';
				if (isset($conversation->meta['whtoken'])) if (isset($config['tokens'])) if (in_array($conversation->meta['whtoken'],$config['tokens'])) $token=$conversation->meta['whtoken'];
				$conversation->setMeta('whtoken',$token);
				$conversation->save();
				$response=self::sendMessage($channel_id,$text,$token,$thread->has_attachments?$thread->attachments:[]);
				error_log('Whapi response = '.json_encode($response));
				if (empty($response['sent'])) {
					\Whapi::log('Error occurred sending Whapi message to '.$channel_id, $conversation->mailbox, true);
					error_log('Error occurred sending Whapi message to '.$channel_id.': '.json_encode($response));
				}
				if (isset($response['message']['id']))
				{
					$thread->setMeta('whapiid',$response['message']['id']);
					$thread->save();
				}
			}
			$thread->setMeta('channel','whapi');
			$thread->save();
			\DB::insert('insert into billing_statistics (type,month,cnt) values (?, ?, ?) ON DUPLICATE KEY UPDATE `cnt`=`cnt`+?', ["whapi_out", date('Y-m'),1,1]);

        }, 20, 4);

		\Eventy::addAction('conversation.user_replied', function($conversation, $thread) {
			$thread = $thread->fresh();
            if ($thread->type!=Thread::TYPE_MESSAGE) return;
			if ($conversation->channel != self::CHANNEL) {
                return;
            }
			$mailbox = $conversation->mailbox;
			$voipe_settings = $mailbox->meta[\VoipeIntegration::DRIVER] ?? [];
			if (isset($voipe_settings['joinconversations'])) return;

            $customer=$conversation->customer;
			$channel_id = $customer->getChannelId(self::CHANNEL);

            if (!$channel_id) {
                \Whapi::log('Can not send a reply to the customer', $conversation->mailbox, true);
				error_log('Can not send a reply to the customer ('.$customer->id.': '.$customer->getFullName().'): customer has no messenger ID.');
                return;
            }

            if ($thread->isDraft() || $thread->imported) {
                return;
            }

            $text = $thread->getBodyAsText();

            $config = $conversation->mailbox->meta[\Whapi::DRIVER] ?? [];

			//if session is open
			$wsent=false;
			if (isset($customer->meta['whapilastdate'])) if (microtime(true)-$customer->meta['whapilastdate']<86000)
			{
				$wsent=true;
				$token=$config['token']??'';
				if (isset($conversation->meta['whtoken'])) if (isset($config['tokens'])) if (in_array($conversation->meta['whtoken'],$config['tokens'])) $token=$conversation->meta['whtoken'];
				$conversation->setMeta('whtoken',$token);
				$conversation->save();
				$response=self::sendMessage($channel_id,$text,$token,$thread->has_attachments?$thread->attachments:[]);
				error_log('Whapi response = '.json_encode($response));
				if (empty($response['sent'])) {
					\Whapi::log('Error occurred sending Whapi message to '.$channel_id, $conversation->mailbox, true);
					error_log('Error occurred sending Whapi message to '.$channel_id.': '.json_encode($response));
				}
				if (isset($response['message']['id']))
				{
					$thread->setMeta('whapiid',$response['message']['id']);
					$thread->save();
				}
			}
			$thread->setMeta('channel','whapi');
			$thread->save();
			\DB::insert('insert into billing_statistics (type,month,cnt) values (?, ?, ?) ON DUPLICATE KEY UPDATE `cnt`=`cnt`+?', ["whapi_out", date('Y-m'),1,1]);

        }, 20, 2);

		\Eventy::addAction('conversation.send_reply_save', function($conversation, $request) {

			if ($request->is_whapi==1||$conversation->channel == self::CHANNEL)
			{
				$conversation->channel = self::CHANNEL;
				$customer=$conversation->customer;
            	$mailbox = $conversation->mailbox;
				$channel_id = $request->whapiphone;
				$conversation->save();

			}
        }, 20, 2);

		\Eventy::addAction('workflows.values_custom', function($type, $value, $mode, $and_i, $row_i, $data) {
			if ($type != 'whapi') {
				return;
			}
			echo '&nbsp; <a href="'.route('mailboxes.whapi.ajax_html', ['action' => 'wf_whapi', 'mailbox_id' => $data['mailbox']->id]).'" class="wf-email-modal" data-modal-title="'.__('Edit Whapi').'" data-modal-no-footer="true" data-modal-on-show="initWfEmailForm">'. __('Customize').'..</a>
						<input class="form-control" type="hidden" value="'.htmlspecialchars($value).'" name="'.$mode.'['.$and_i.']['.$row_i.'][value]" disabled />';
		}, 20, 6);


		\Eventy::addFilter('workflows.actions_config', function($actions) {
			$actions['dummy']['items']['whapi'] = [
				'title' => __('Send Whapi'),
                'values_custom' => true,
			];
			return $actions;
        }, 20, 1);

        \Eventy::addFilter('workflow.perform_action', function($performed, $type, $operator, $value, $conversation, $workflow) {
			error_log('Perform Action '.$type);
			if ($type == 'whapi') {
				error_log('W PERFORMED '.json_encode($performed));
				error_log('W OPERATOR '.json_encode($operator));
				error_log('W VALUE '.json_encode($value));
				error_log('W CONVERSATION ID '.json_encode($conversation->id));
				error_log('W workflow '.json_encode($workflow));
				error_log('W CUSTOMER '.json_encode($conversation->customer));
				
				try {
					$value = json_decode($value ?? '', true);
				} catch (\Exception $e) {
					error_log('VALUE STRANGE '.json_encode($value));
					return true;
				}

				$customer=$conversation->customer;
				$user = User::where('id', $conversation->user_id)->first();
			    if ($value['phone']=='')
				{
					$dst = $customer->getChannelId(self::CHANNEL);
					if (!$dst) $dst=$customer->getMainPhoneValue();
				}
				else
				{
					$dst = strip_tags($conversation->replaceTextVars($value['phone'], ['user' => $user]));
					// $dst = $value['phone'];
				}
				if (!$dst) {
					\Whapi::log('Can not send a reply to the customer', $conversation->mailbox, true);
					error_log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'): customer has no phone number.');
					return true;
				}
				error_log('DST = '.$dst);
				
				$config = $conversation->mailbox->meta[\Whapi::DRIVER] ?? [];
				$token=$config['token']??'';
				error_log('Whapi Workflow token = '.$token);
				// if (isset($conversation->meta['whtoken'])) if (isset($config['tokens'])) if (in_array($conversation->meta['whtoken'],$config['tokens'])) $token=$conversation->meta['whtoken'];
				// error_log('Whapi Workflow token (after conversation) = '.$token);
				if (isset($value['wh_channel'])) if (isset($config['tokens'])) if (isset($config['tokens'][$value['wh_channel']-1])) $token=$config['tokens'][$value['wh_channel']-1];
				error_log('Whapi Workflow token (after value) = '.$token);
				$conversation->setMeta('whtoken',$token);
				$conversation->save();
				$response=\Whapi::sendMessage($dst,$value['text'],$token,[]);
				if (empty($response['sent'])) {
					\Whapi::log('Error occurred sending Whapi message to '.$dst, $conversation->mailbox, true);
					error_log('Error occurred sending Whapi message to '.$dst.': '.json_encode($response));
				}
				else
				{
					$created_by_user_id = \Workflow::getUser()->id;
					Thread::createExtended([
							'type' => Thread::TYPE_NOTE,
							'created_by_user_id' => $created_by_user_id,
							'body' => $value['text'],
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

		\Eventy::addAction('notifications_table.general.append', function($vars) {
            echo \View::make('whapi::partials/notifications_table', $vars)->render();
        }, 20, 1);

		\Eventy::addFilter('subscription.events_by_type', function($events, $event_type, $thread) {
			if ($event_type == self::EVENT_NONHEALTH) {
				$events[] = self::EVENT_NONHEALTH;
			}
            return $events;
		}, 20, 3);

		// Schedule background processing.
        \Eventy::addFilter('schedule', function($schedule) {
            // error_log('Whapi scheduling');
			$schedule->call(function () {
				error_log('Whapi scheduling IN');
				\Whapi::cronMetrics();
			// })->name('module_whapi_health_check')->withoutOverlapping();
			})->cron("48 11 * * *")->name('module_whapi_health_check')->withoutOverlapping();
			// \Whapi::cronMetrics();
			return $schedule;
        }, 20, 1);

        //     return $events;
        // }, 20, 3);

		// \Eventy::addFilter('subscription.filter_out', function($filter_out, $subscription, $thread) {
        //     if ($subscription->event != self::EVENT_I_AM_MENTIONED) {
        //         return $filter_out;
        //     }
        //     $mentioned_users = self::getMentionedUsers($thread->body);
        //     if (!in_array($subscription->user_id, $mentioned_users)) {
        //         return true;
        //     } else {
        //         return false;
        //     }
        // }, 20, 3);

		// \Eventy::addFilter('subscription.is_related_to_user', function($is_related, $subscription, $thread) {
        //     if ($subscription->event == self::EVENT_I_AM_MENTIONED
        //         && in_array($subscription->user_id, self::getMentionedUsers($thread->body))
        //     ) {
        //         return true;
        //     }

        //     return $is_related;
        // }, 20, 3);

		// // Always show @mentions notification in the menu.
        // \Eventy::addFilter('subscription.users_to_notify', function($users_to_notify, $event_type, $events, $thread) {
        //     if (in_array(self::EVENT_I_AM_MENTIONED, $events)) {
        //         $mentioned_users = self::getMentionedUsers($thread->body);
        //         if (count($mentioned_users)) {
        //             $users = User::whereIn('id', $mentioned_users)->get();
        //             foreach ($users as $user) {
        //                 $users_to_notify[Subscription::MEDIUM_MENU][] = $user;
        //                 $users_to_notify[Subscription::MEDIUM_MENU] = array_unique($users_to_notify[Subscription::MEDIUM_MENU]);
        //             }
        //         }
        //     }

        //     return $users_to_notify;
        // }, 20, 4);

		\Eventy::addFilter('voipe.default_channels', function($channels,$mailbox) {
			$settings = $mailbox->meta[self::DRIVER] ?? [];
			error_log('Default Whapi before = '.json_encode($channels));
			if (isset($settings['token'])) $channels['whapi_0']=[
				'type'=>'whapi',
				'name'=>'Whapi '.($settings['channel_name'] ?? ''),
				'id'=>self::CHANNEL.'_0',
			];
			if (isset($settings['tokens'])) foreach ($settings['tokens'] as $tk=>$token)
			{
				$channels['whapi_'.($tk+1)]=[
					'type'=>'whapi',
					'name'=>'Whapi '.($settings['channels_names'][$tk] ?? ''),
					'id'=>self::CHANNEL.'_'.($tk+1),
				];
			}
			error_log('Default Whapi after = '.json_encode($channels));
			return $channels;
		}, 20, 2);

		\Eventy::addFilter('voipeintegration.channel_id', function($channel_id, $customer) {
            if (!$channel_id) $channel_id = $customer->getChannelId(self::CHANNEL);
            return $channel_id;
        }, 20, 2);
    }

	public static function processIncomingMessage($user_phone, $user_name, $channel_id, $text, $mailbox, $attachments = [], $token='', $channel_name='')
    {
		error_log('Whapi Process Incoming : '.json_encode($user_phone).' '.$channel_id);
        if (in_array($text, self::$skip_messages) && empty($attachments)) {
            return false;
        }

		if (!$user_phone && !$user_name) {
			error_log('Empty user.');
			return;
		}

		if (!$user_name) {
			$user_name = $user_phone;
		}

        // Get or creaate a customer.
        $channel = \Whapi::CHANNEL;
        
        $customer = Customer::getCustomerByChannel($channel, $channel_id);
		error_log('Whapi Customer1 : '.json_encode(@$customer->phones));
        
        // Try to find customer by phone number.
        if (!$customer) {
            // Get first customer by phone number.
            $customer = Customer::findByPhone($user_phone);
            error_log('Whapi Customer2 : '.json_encode(@$customer->phones));
        	// For now we are searching for a customer without a channel
            // and link the obtained customer to the channel.
            if ($customer) {
                $customer->addChannel($channel, $channel_id);
				error_log('Whapi Customer3 : '.json_encode(@$customer->phones));
			}
        }

        if (!$customer) {
            $customer_data = [
                // These two lines will add a record to customer_channel via observer.
                'channel' => $channel,
                'channel_id' => $channel_id,
                'first_name' => $user_name,
                'last_name' => '',
                'phones' => Customer::formatPhones([$user_phone])
            ];

            $customer = Customer::createWithoutEmail($customer_data);
			error_log('Whapi Customer4 : '.json_encode(@$customer->phones));
        
            if (!$customer) {
                error_log('Could not create a customer.');
                return;
            }
        }

		$customer->setMeta('whapilastdate',microtime(true));
		$customer->save();
		error_log('Whapi Customer5 : '.json_encode(@$customer->phones));
        error_log('Whapi Customer5 : '.json_encode(@$customer->id));
        
        // Get last customer conversation or create a new one.
        $voipe_settings = $mailbox->meta[\VoipeIntegration::DRIVER] ?? [];
		if (isset($voipe_settings['joinconversations']))
		{
			// Get last customer conversation or create a new one.
			$conversation = Conversation::where('mailbox_id', $mailbox->id)
				->where('customer_id', $customer->id);
			if (!isset($voipe_settings['reopenconversations']))
			{
				$conversation=$conversation->where('status','<>',Conversation::STATUS_CLOSED);
			}
			$conversation=$conversation->orderBy('created_at', 'desc')->first();
		}
		else
		{
			$conversation = Conversation::where('mailbox_id', $mailbox->id)
				->where('customer_id', $customer->id)
				->where('channel', $channel);
			if (!isset($voipe_settings['reopenconversations']))
			{
				$conversation=$conversation->where('status','<>',Conversation::STATUS_CLOSED);
			}
			$conversation=$conversation->orderBy('created_at', 'desc')->first();
		}

        if ($conversation) {
			$conversation->status=Conversation::STATUS_ACTIVE;
			$conversation->closed_at=null;
			$conversation->closed_by_user_id=null;
			$conversation->updateFolder();
			$conversation->setMeta('whtoken',$token);
			$conversation->save();
			error_log('Create thread customer_id '.json_encode($customer->id));
			error_log('Create thread body '.json_encode($text));
			error_log('Create thread attachments '.json_encode($attachments));
			
            // Create thread in existing conversation.
            $thread = Thread::createExtended([
                    'type' => Thread::TYPE_CUSTOMER,
                    'customer_id' => $customer->id,
                    'body' => $text,
                    'attachments' => $attachments,
                ],
                $conversation,
                $customer
            );
			$thread->setMeta('channel','whapi');
			$thread->save();
			if($thread) {
				if($user_name == "Mobile") {
					$thread->type = '2';
					$thread->created_by_customer_id = null;
					$thread->created_by_user_id = User::where("first_name", "Mobile")->first()->id;
					$thread->save();
				}
			}
        } else {
            // Create conversation.
            $res=Conversation::create([
                    'type' => Conversation::TYPE_CHAT,
                    'subject' => Conversation::subjectFromText($text),
                    'mailbox_id' => $mailbox->id,
                    'source_type' => Conversation::SOURCE_TYPE_WEB,
                    'channel' => $channel,
                ], [[
                    'type' => Thread::TYPE_CUSTOMER,
                    'customer_id' => $customer->id,
                    'body' => $text,
                    'attachments' => $attachments,
                ]],
                $customer
            );
			$res['conversation']->setMeta('whtoken',$token);
			$res['conversation']->save();
			$res['thread']->setMeta('channel','whapi');
			$res['thread']->save();
        }
	}

	public static function getWebhookUrl($mailbox_id,$token='')
    {
        return route('whapi.webhook', [
            'mailbox_id' => $mailbox_id,
            'mailbox_secret' => \Whapi::getMailboxSecret($mailbox_id.'_'.$token)
        ]);
    }

	public static function getQrUrl($mailbox_id,$token='')
    {
        return route('mailboxes.whapi.channelsqr', [
            'mailbox_id' => $mailbox_id,
            'mailbox_secret' => \Whapi::getMailboxSecret($mailbox_id.'_'.$token)
        ]);
    }

	public static function getDeactivateUrl($mailbox_id,$token='')
    {
        return route('mailboxes.whapi.channelslogout', [
            'mailbox_id' => $mailbox_id,
            'mailbox_secret' => \Whapi::getMailboxSecret($mailbox_id.'_'.$token)
        ]);
    }

	public static function processOwnMessage($user, $customer_phone, $text, $mailbox, $attachments = [], $chat_name = '')
    {
        if (in_array($text, self::$skip_messages) && empty($attachments)) {
			error_log('Skip text');
            return false;
        }

        if (!$user) {
        	error_log('No user');
			error_log('Empty user when processing own message.');
            return;
        }

        // Get or creaate a customer.
        $channel = \Whapi::CHANNEL;
        $channel_id = $customer_phone;
        $conversation = null;

        $customer = Customer::where('channel', $channel)
            ->where('channel_id', $channel_id)
            ->first();

        if ($customer) {
            // Get last customer conversation.
            $conversation = Conversation::where('mailbox_id', $mailbox->id)
                ->where('customer_id', $customer->id)
                ->where('channel', $channel)
                ->orderBy('created_at', 'desc')
                ->first();
        } else {

            $customer_data = [
                'channel' => $channel,
                'channel_id' => $channel_id,
                'first_name' => $chat_name,
                'last_name' => '',
                'phones' => Customer::formatPhones([$customer_phone])
            ];

            $customer = Customer::createWithoutEmail($customer_data);

            if (!$customer) {
                error_log('Could not create a customer.');
                error_log('cant create customer');
				return;
            }
        }

        if ($conversation) {
            // Create thread in existing conversation.
            Thread::createExtended([
                    'type' => Thread::TYPE_MESSAGE,
                    'created_by_user_id' => $user->id,
                    'body' => $text,
                    'attachments' => $attachments,
                    'imported' => true,
                ],
                $conversation,
                $customer
            );
        } else {
            // Create conversation.
            Conversation::create([
                    'type' => Conversation::TYPE_CHAT,
                    'subject' => Conversation::subjectFromText($text),
                    'mailbox_id' => $mailbox->id,
                    'source_type' => Conversation::SOURCE_TYPE_WEB,
                    'channel' => $channel,
                ], [[
                    'type' => Thread::TYPE_MESSAGE,
                    'created_by_user_id' => $user->id,
                    'body' => $text,
                    'attachments' => $attachments,
                    'imported' => true,
                ]],
                $customer
            );
           //error_log('Could not find customer conversation when importing own message sent directly from Whapi on mobile phone as a response to customer. Customer phone number: '.$customer_phone);
        }
		error_log('Own msg added');
    }

	public static function getMailboxSecret($id)
    {
        return crc32(config('app.key').$id.'salt'.self::SALT);
    }

	public static function log($text, $mailbox = null, $is_webhook = true)
    {
        \Helper::log(\Whapi::LOG_NAME, '['.self::CHANNEL_NAME.($is_webhook ? ' Webhook' : '').' ] '.($mailbox ? '('.$mailbox->name.') ' : '').$text);
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
            __DIR__.'/../Config/config.php' => config_path('whapi.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'whapi'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/whapi');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/whapi';
        }, \Config::get('view.paths')), [$sourcePath]), 'whapi');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
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

	public static function sendDocumentMessage($channel_id,$token,$url,$filename='')
	{
		try 
		{
			$json=[
				'to' => $channel_id,
                 'media' => $url,
			];
			if ($filename!='') $json['filename']=$filename;

            $response = (new \GuzzleHttp\Client([
						'base_uri' => 'https://gate.whapi.cloud/',
						'timeout'  => 10.0,
					]))->post('messages/document', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $json
            ]);

            // $result = json_decode($response->getBody(), true);
        	if ($response->getStatusCode() == 200) {
				$result = \Helper::jsonToArray($response->getBody()->getContents());
				$result['result']=true;
				return $result;
				// return \Helper::jsonToArray($response->getBody()->getContents());
			} else {
				return [
					'result' => false,
					'msg' => 'API error: '.$response->getStatusCode(),
				];
			}
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return [
                'result' => false,
                'msg' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'result' => false,
                'msg' => $e->getMessage(),
            ];
        }
	}

	public static function sendTextMessage($channel_id,$text,$token)
	{
		try 
		{
			error_log('Whapi payload = '.json_encode(
				[
                    'to' => $channel_id,
                    'body' => $text,
				]
			));
            $response = (new \GuzzleHttp\Client([
						'base_uri' => 'https://gate.whapi.cloud/',
						'timeout'  => 10.0,
					]))->post('messages/text', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'to' => $channel_id,
                    'body' => $text,
				]
            ]);

            // $result = json_decode($response->getBody(), true);
        	if ($response->getStatusCode() == 200) {
				return \Helper::jsonToArray($response->getBody()->getContents());
			} else {
				return [
					'result' => false,
					'msg' => 'API error: '.$response->getStatusCode(),
				];
			}
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return [
                'result' => false,
                'msg' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'result' => false,
                'msg' => $e->getMessage(),
            ];
        }
	}


	public static function setWebhookUrl($mailbox_id,$token='')
	{
		try 
		{
			$json=[
				'webhooks' => [
					[
						'url'=>self::getWebhookUrl($mailbox_id,$token),
						'events'=>[
							[
								'type'=>'messages',
								'method'=>'post'
							],
							[
								'type'=>'statuses',
								'method'=>'post'
							],
							[
								'type'=>'users',
								'method'=>'post'
							],
						],
					],
				],
			];
			error_log('Set Webhook Url ('.json_encode($json).')');
            $response = (new \GuzzleHttp\Client([
						'base_uri' => 'https://gate.whapi.cloud/',
						'timeout'  => 10.0,
					]))->PATCH('settings', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $json,
            ]);

            // $result = json_decode($response->getBody(), true);
        	if ($response->getStatusCode() == 200) {
				$result = \Helper::jsonToArray($response->getBody()->getContents());
				error_log('Set Webhook Result = '.json_encode($result));
				return $result;
			} else {
				$result = \Helper::jsonToArray($response->getBody()->getContents());
				error_log('Set Webhook Result = ('.$response->getStatusCode().') '.json_encode($result));
				return [
					'result' => false,
					'msg' => 'API error: '.$response->getStatusCode(),
				];
			}
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            error_log('Set Webhook '.$e->getMessage());
			return [
                'result' => false,
                'msg' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            error_log('Set Webhook '.$e->getMessage());
			return [
                'result' => false,
                'msg' => $e->getMessage(),
            ];
        }
	}

	public static function getMedia($media_id,$token)
	{
		try 
		{
			error_log('Whapi get media = '.$media_id);
            $response = (new \GuzzleHttp\Client([
						'base_uri' => 'https://gate.whapi.cloud/',
						'timeout'  => 10.0,
					]))->request('GET','media/'.$media_id, [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                ]
            ]);

            // $result = json_decode($response->getBody(), true);
        	if ($response->getStatusCode() == 200) {
				return $response->getBody()->getContents();
			} else {
				error_log('API error: '.$response->getStatusCode());
				return false;
			}
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            error_log($e->getMessage());
			return false;
        } catch (\Exception $e) {
            error_log($e->getMessage());
			return false;
        }
	}

	public static function getQrBase64($token)
	{
		try 
		{
			error_log('sending https://gate.whapi.cloud/users/login TOKEN='.$token);
			$response = (new \GuzzleHttp\Client([
						'base_uri' => 'https://gate.whapi.cloud/',
						'timeout'  => 10.0,
					]))->request('GET','users/login', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    // 'Content-Type' => 'application/json',
                ]
            ]);

            // $result = json_decode($response->getBody(), true);
        	if ($response->getStatusCode() == 200) {
				$result=json_decode($response->getBody()->getContents(),true);
				error_log('Response = '.json_encode($result));
				return $result;
			} else {
				error_log('API error: '.$response->getStatusCode());
				return false;
			}
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            error_log($e->getMessage());
			return false;
        } catch (\Exception $e) {
            error_log($e->getMessage());
			return false;
        }
	}

	public static function tokenLogout($token)
	{
		try 
		{
			error_log('sending https://gate.whapi.cloud/users/logout TOKEN='.$token);
			$response = (new \GuzzleHttp\Client([
						'base_uri' => 'https://gate.whapi.cloud/',
						'timeout'  => 10.0,
					]))->request('GET','users/logout', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    // 'Content-Type' => 'application/json',
                ]
            ]);

            // $result = json_decode($response->getBody(), true);
        	if ($response->getStatusCode() == 200) {
				$result=json_decode($response->getBody()->getContents(),true);
				error_log('Response = '.json_encode($result));
				return $result;
			} else {
				error_log('API error: '.$response->getStatusCode());
				return false;
			}
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            error_log($e->getMessage());
			return false;
        } catch (\Exception $e) {
            error_log($e->getMessage());
			return false;
        }
	}

	public static function getSettings($token)
	{
		try 
		{
			$response = (new \GuzzleHttp\Client([
						'base_uri' => 'https://gate.whapi.cloud/',
						'timeout'  => 10.0,
					]))->request('GET','settings', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    // 'Content-Type' => 'application/json',
                ]
            ]);

            // $result = json_decode($response->getBody(), true);
        	if ($response->getStatusCode() == 200) {
				$result=json_decode($response->getBody()->getContents(),true);
				error_log('Response = '.json_encode($result));
				return $result;
			} else {
				error_log('API error: '.$response->getStatusCode());
				return false;
			}
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            error_log($e->getMessage());
			return false;
        } catch (\Exception $e) {
            error_log($e->getMessage());
			return false;
        }
	}

	public static function getHealth($token)
	{
		try 
		{
			$response = (new \GuzzleHttp\Client([
						'base_uri' => 'https://gate.whapi.cloud/',
						'timeout'  => 10.0,
					]))->request('GET','health', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    // 'Content-Type' => 'application/json',
                ]
            ]);

            // $result = json_decode($response->getBody(), true);
        	if ($response->getStatusCode() == 200) {
				$result=json_decode($response->getBody()->getContents(),true);
				error_log('Response = '.json_encode($result));
				return $result;
			} else {
				error_log('API error: '.$response->getStatusCode());
				return false;
			}
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            error_log($e->getMessage());
			return false;
        } catch (\Exception $e) {
            error_log($e->getMessage());
			return false;
        }
	}

	public static function sendMessage($channel_id,$text,$token,$attachments=[])
    {
		foreach ($attachments as $attachment) 
		{
			self::sendDocumentMessage($channel_id,$token,$attachment->url(),$attachment->file_name);
			if (empty($response['sent'])) {
				error_log('Error occurred sending file via Whapi to '.$channel_id);
			}
		}

		$params = [
			'body' => $text,
			'phone' => $channel_id,
		];
		\DB::insert('insert into billing_statistics (type,month,cnt) values (?, ?, ?) ON DUPLICATE KEY UPDATE `cnt`=`cnt`+?', ["whapi_out", date('Y-m'),1,1]);

		return self::sendTextMessage($channel_id,$text,$token);
	}

	public static function cronMetricsSendNotification($conversation,$data)
	{
		// Mediums
		// const MEDIUM_EMAIL = 1; // This is also website notifications
		// const MEDIUM_BROWSER = 2; // Browser push notification
		// const MEDIUM_MOBILE = 3;
		// const MEDIUM_MENU = 10; // Notifications menu
		$mediums = [
			1,
			2,
			3,
		];
		// Get mailbox users ids
		$mailbox_user_ids = [];
		// Get users and threads from previous results to avoid repeated SQL queries.
		$users = [];
		$threads = [];
		$threads = $conversation->getThreads();
		$users_to_notify = Subscription::usersToNotify(self::EVENT_NONHEALTH, $conversation, $threads, $mailbox_user_ids);
		error_log('Cron Metrics Notification users_to_notify = '.json_encode($users_to_notify));
		if (!$users_to_notify || !is_array($users_to_notify)) 
		{
			return;
        }
		$notify=[];
		foreach ($users_to_notify as $medium => $medium_users_to_notify) 
		{
			if (count($medium_users_to_notify)) 
			{
				$notify[$medium][$conversation->id] = [
					// Users subarray contains all users who need to receive notification
					// for all events for the media.
					'users'            => array_unique(array_merge($users[$medium] ?? [], $medium_users_to_notify)),
					'conversation'     => $conversation,
					'threads'          => $threads,
					'mailbox_user_ids' => $mailbox_user_ids,
					'data'=>$data,
				];
            }
        }

        // - Email notification (better to create them first)
        if (!empty($notify[1])) {
            foreach ($notify[1] as $conversation_id => $notify_info) {
                // \App\Jobs\SendNotificationToUsers::dispatch($notify_info['users'], $notify_info['conversation'], $notify_info['threads'])
                $mailbox = $notify_info['conversation']->mailbox;
				\App\Misc\Mail::setMailDriver($mailbox, null, $notify_info['conversation']);
				$threads = Thread::sortThreads($notify_info['threads']);
				$headers = [];
				$last_thread = $notify_info['threads']->first();
				if (!$last_thread) {
					continue;
				}
				if ($last_thread->isDraft()) {
					continue;
				}
				if (config('app.email_user_history') == 'last') {
					$notify_info['threads'] = $notify_info['threads']->slice(0, 2);
				}
				if (config('app.email_user_history') == 'none') {
					$notify_info['threads'] = $notify_info['threads']->slice(0, 1);
				}
				$prev_message_id = \App\Misc\Mail::MESSAGE_ID_PREFIX_NOTIFICATION_IN_REPLY.'-'.$notify_info['conversation']->id.'-'.md5($notify_info['conversation']->id).'@'.$mailbox->getEmailDomain();
				$headers['In-Reply-To'] = '<'.$prev_message_id.'>';
				$headers['References'] = '<'.$prev_message_id.'>';
				$headers['X-Auto-Response-Suppress'] = 'All';
				$global_exception = null;
				foreach ($notify_info['users'] as $user) {
					if (!isset($user->id)) {
						continue;
					}
					if ($user->isDeleted()) {
						continue;
					}
					$message_id = \App\Misc\Mail::MESSAGE_ID_PREFIX_NOTIFICATION.'-'.$last_thread->id.'-'.$user->id.'-'.time().'@'.$mailbox->getEmailDomain();
					$headers['Message-ID'] = $message_id;
					$from_name = '';
					if ($last_thread->type == Thread::TYPE_CUSTOMER) {
						$from_name = '';
						if ($last_thread->customer) {
							$from_name = $last_thread->customer->getFullName(true, true);
						}
						if ($from_name) {
							$from_name = $from_name.' '.__('via').' '.$mailbox->name;
						}
					}
					if (!$from_name) {
						$from_name = $mailbox->name;
					}
					$from = ['address' => $mailbox->email, 'name' => $from_name];
					app()->setLocale($user->getLocale());
					$headers['X-FreeScout-Mail-Type'] = 'user.notification';
					$headers = \Eventy::filter('jobs.send_reply_to_customer.headers', $headers, $user, $mailbox, $notify_info['conversation'], $notify_info['threads'], $from);
					$exception = null;
					try {
						error_log('Cron Metrics Notification '.json_encode($data));
						Mail::to([['name' => $user->getFullName(), 'email' => $user->email]])
							->send(new UserNotificationWhapiHealth($user, $notify_info['conversation'], $notify_info['threads'], $headers, $from, $mailbox,$data));
					} catch (\Exception $e) {
						error_log('Cron Metrics Notification exception'.$e->getMessage());
						// We come here in case SMTP server unavailable for example
						activity()
							->causedBy($user)
							->withProperties([
								'error'    => $e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')',
							])
							->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
							->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_TO_USER);

						$exception = $e;
						$global_exception = $e;
					}
					$status_message = '';
					if ($exception) {
						$status = SendLog::STATUS_SEND_ERROR;
						$status_message = $exception->getMessage();
					} else {
						$failures = Mail::failures();
						if (!empty($failures) && in_array($user->email, $failures)) {
							$status = SendLog::STATUS_SEND_ERROR;
						} else {
							$status = SendLog::STATUS_ACCEPTED;
						}
					}
					SendLog::log($last_thread->id, $message_id, $user->email, SendLog::MAIL_TYPE_USER_NOTIFICATION, $status, null, $user->id, $status_message);
				}
            }
        }

        // - Menu notification (uses same medium as for Email, if email notifications are disabled - use Browser notificaitons)
        if (!empty($notify[1]) 
            || !empty($notify[2])
            || !empty($notify[10])
        ) {
            if (!empty($notify[1])) {
                $notify_menu = $notify[1] ?? [];
            } else {
                $notify_menu = $notify[2] ?? [];
            }
            $notify_menu = $notify_menu + ($notify[10] ?? []);
            foreach ($notify_menu as $notify_info) {
                $website_notification = new WebsiteNotification($notify_info['conversation'], Subscription::chooseThread($notify_info['threads']));
                // $website_notification->delay($delay);
                \Notification::send($notify_info['users'], $website_notification);
            }
        }

        // // Send broadcast notifications:
        // // - Browser push notification
        // $broadcasts = [];
        // foreach ([1, 2] as $medium) {
        //     if (empty($notify[$medium])) {
        //         continue;
        //     }
        //     foreach ($notify[$medium] as $notify_info) {
        //         $thread_id = self::chooseThread($notify_info['threads'])->id;

        //         foreach ($notify_info['users'] as $user) {
        //             $broadcast_id = $thread_id.'_'.$user->id;
        //             $mediums = [$medium];
        //             if (!empty($broadcasts[$broadcast_id]['mediums'])) {
        //                 $mediums = array_unique(array_merge($mediums, $broadcasts[$broadcast_id]['mediums']));
        //             }
        //             $broadcasts[$broadcast_id] = [
        //                 'user'         => $user,
        //                 'conversation' => $notify_info['conversation'],
        //                 'threads'      => $notify_info['threads'],
        //                 'mediums'      => $mediums,
        //             ];
        //         }
        //     }
        // }
        // // \Notification::sendNow($notify_info['users'], new BroadcastNotification($notify_info['conversation'], $notify_info['threads'][0]));
        // foreach ($broadcasts as $broadcast_id => $to_broadcast) {
        //     $broadcast_notification = new BroadcastNotification($to_broadcast['conversation'], self::chooseThread($to_broadcast['threads']), $to_broadcast['mediums']);
        //     $broadcast_notification->delay($delay);
        //     $to_broadcast['user']->notify($broadcast_notification);
        // }

        // // - Mobile
        // \Eventy::action('subscription.process_events', $notify);

        // self::$occurred_events = [];
    }

	public static function cronMetrics()
	{
		error_log('Begin cronMetrics');
		$all_mailboxes = Mailbox::all();
		if ($all_mailboxes) foreach ($all_mailboxes as $mailbox)
		{
			$config = $mailbox->meta[\Whapi::DRIVER] ?? [];
			error_log('cronMetrics '.$mailbox->id.' config = '.json_encode($config));
			if (!empty($config['enabled']))
			{
				$conversation = Conversation::where('mailbox_id', $mailbox->id)->orderBy('created_at', 'desc')->first();
				$user = User::where('id', $conversation->user_id)->first();
				$tokens=[];
				$tokens[]=$config['token']??'';
				if (isset($config['tokens'])) foreach ($config['tokens'] as $token)
				{
					$t=trim($token);
					if ($t!='') $tokens[]=$t;
				}
				foreach ($tokens as $token) if ($token!='')
				{
					try 
					{
						$response = (new \GuzzleHttp\Client([
									'base_uri' => 'https://tools.whapi.cloud/',
									'timeout'  => 10.0,
								]))->post('services/riskOfBlocking', [
							'headers' => [
								'Authorization' => 'Bearer '.$token,
								// 'Content-Type' => 'application/json',
							],
							// 'json' => [
							// 	'to' => $channel_id,
							// 	'body' => $text,
							// ]
						]);
						if ($response->getStatusCode() == 200) {
							$healthy=true;
							$health = \Helper::jsonToArray($response->getBody()->getContents());
							if (!isset($config['health']))
							{
								$config['health']=[
									'lifeTime'=>1,
									'riskFactor'=>1,
									'riskFactorChats'=>1,
									'riskFactorContacts'=>1,
								];
							}

							error_log('Mailbox '.$mailbox->id.' cronMetrics config='.json_encode($config['health']));
							error_log('Mailbox '.$mailbox->id.' cronMetrics response='.json_encode($health));
							if (@$health['lifeTime']!=3) if (@$health['lifeTime']!=$config['health']['lifeTime']) $healthy=false;
							if (@$health['riskFactor']!=3) if (@$health['riskFactor']!=$config['health']['riskFactor']) $healthy=false;
							if (@$health['riskFactorChats']!=3) if (@$health['riskFactorChats']!=$config['health']['riskFactorChats']) $healthy=false;
							if (@$health['riskFactorContacts']!=3) if (@$health['riskFactorContacts']!=$config['health']['riskFactorContacts']) $healthy=false;
							$config['health']=$health;
							$mailbox->setMetaParam(\Whapi::DRIVER, $config);
							$mailbox->save();
							if (!$healthy) 
							{
								$indicators=[
									'1'=>'Use caution',
									'2'=>'Needs Attention',
									'3'=>'Good Indicator',
								];
								if (isset($health['lifeTime'])) if (isset($indicators[(int)$health['lifeTime']])) $health['lifeTime']=$indicators[(int)$health['lifeTime']];
								if (isset($health['riskFactor'])) if (isset($indicators[(int)$health['riskFactor']])) $health['riskFactor']=$indicators[(int)$health['riskFactor']];
								if (isset($health['riskFactorChats'])) if (isset($indicators[(int)$health['riskFactorChats']])) $health['riskFactorChats']=$indicators[(int)$health['riskFactorChats']];
								if (isset($health['riskFactorContacts'])) if (isset($indicators[(int)$health['riskFactorContacts']])) $health['riskFactorContacts']=$indicators[(int)$health['riskFactorContacts']];
								
								if (empty($health))
								{
									$health=[];
								}
								$this->cronMetricsSendNotification($conversation,$health);
								// error_log('cronMetrics register event '.$conversation->id);
								// Subscription::registerEvent(\Whapi::EVENT_NONHEALTH, $conversation, 0);
							}
							error_log('API response: '.json_encode($health));
						} else {
							Subscription::registerEvent(\Whapi::EVENT_NONHEALTH, $conversation, 0);
							error_log('API error: '.$response->getStatusCode());
						}
					} catch (\GuzzleHttp\Exception\RequestException $e) {
						Subscription::registerEvent(\Whapi::EVENT_NONHEALTH, $conversation, 0);
						error_log($e->getMessage());
					} catch (\Exception $e) {
						Subscription::registerEvent(\Whapi::EVENT_NONHEALTH, $conversation, 0);
						error_log($e->getMessage());
					}
				}
			}
		}
	}

	public static function getMetrics($token)
	{
		try 
		{
			$response = (new \GuzzleHttp\Client([
						'base_uri' => 'https://tools.whapi.cloud/',
						'timeout'  => 10.0,
					]))->post('services/riskOfBlocking', [
				'headers' => [
					'Authorization' => 'Bearer '.$token,
					// 'Content-Type' => 'application/json',
				],
				// 'json' => [
				// 	'to' => $channel_id,
				// 	'body' => $text,
				// ]
			]);
			
			if ($response->getStatusCode() == 200) {
				return \Helper::jsonToArray($response->getBody()->getContents());
			} else {
				error_log('API error: '.$response->getStatusCode());
				return false;
			}
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            error_log($e->getMessage());
			return false;
        } catch (\Exception $e) {
            error_log($e->getMessage());
			return false;
        }
	}

}
