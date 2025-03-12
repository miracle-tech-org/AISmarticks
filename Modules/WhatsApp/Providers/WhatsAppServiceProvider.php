<?php

namespace Modules\WhatsApp\Providers;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use App\Folder;
use App\Follower;
use App\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\Storage;

//require_once __DIR__.'/../vendor/autoload.php';

class WhatsAppServiceProvider extends ServiceProvider
{
    const DRIVER = 'whatsapp';

    // Communication channel.
    // The value must be between 1 and 255. Official modules use channel numbers below 100.
    // Non-official - above 100. To avoid conflicts with other modules contact FreeScout Team
    // to get your Channel number: https://freescout.net/contact-us/
    const CHANNEL = 13;

    const CHANNEL_NAME = 'WhatsApp';

    const SYSTEM_CHATAPI = 1;
    const SYSTEM_TWILIO  = 2;

    const SYSTEM_CHATAPI_NAME  = '1msg.io';

    public static $system_names = [
        self::SYSTEM_CHATAPI => self::SYSTEM_CHATAPI_NAME,
        self::SYSTEM_TWILIO => 'Twilio',
    ];

    const LOG_NAME = 'whatsapp_errors';
    const SALT = 'dk23lsd8';

    const TWILIO_API_URL = 'https://api.twilio.com/2010-04-01/Accounts';

    public static $skip_messages = [
        // '%%%_IMAGE_%%%',
        // '%%%_VIDEO_%%%',
        // '%%%_FILE_%%%',
        // '%%%_AUDIO_%%%',
        // '%%%_LOCATION_%%%',
    ];

    const API_METHOD_TEMPLATES = 'templates';
    const API_METHOD_TEMPLATE_SEND = 'sendTemplate';
    const API_METHOD_TEMPLATE_ADD = 'addTemplate';
    const API_METHOD_TEMPLATE_REMOVE = 'removeTemplate';
    const API_METHOD_SEND = 'sendMessage';
    const API_METHOD_ME = 'me';
    const API_METHOD_MESSAGES = 'messages';
    const API_METHOD_UPLOAD_MEDIA = 'uploadMedia';
    const API_METHOD_SEND_FILE = 'sendFile';
    const API_METHOD_SET_WEBOOK = 'webhook';

    //const API_STATUS_SUCCESS = 'successful';

    // Audios are not supported.
    const API_MESSAGE_TYPE_TEXT = 'chat';
    const API_MESSAGE_TYPE_INTERACTIVE = 'interactive';
    const API_MESSAGE_TYPE_IMAGE = 'image';
    const API_MESSAGE_TYPE_VIDEO = 'video';
    const API_MESSAGE_TYPE_LOCATION = 'location';
    const API_MESSAGE_TYPE_DOCUMENT = 'document';
    const API_MESSAGE_TYPE_AUDIO = 'audio';
    const API_MESSAGE_TYPE_CONTACT = 'vcard';
    const API_MESSAGE_TYPE_STICKER = 'sticker';
    const API_MESSAGE_TYPE_VOICE = 'voice';

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
        // Add item to the mailbox menu
        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('whatsapp::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 35, 1);

        \Eventy::addFilter('menu.selected', function($menu) {
            $menu['whatsapp'] = [
                'mailboxes.whatsapp.settings',
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
                \WhatsApp::log('Can not send a reply to the customer ('.$customer->id.': '.$customer->getFullName().'): customer has no messenger ID.', $conversation->mailbox);
                return;
            }

            // // We send only the last reply.
            // $replies = $replies->sortByDesc(function ($item, $key) {
            //     return $item->id;
            // });
            // $thread = $replies[0];

            if ($thread->isDraft() || $thread->imported) {
                return;
            }

            $text = $thread->getBodyAsText();

            $config = $conversation->mailbox->meta[\WhatsApp::DRIVER] ?? [];

            if (self::getSystem($config) == self::SYSTEM_CHATAPI) {
                //if session is open
				$wsent=false;
				if (isset($customer->meta['wlastdate'])) if (microtime(true)-$customer->meta['wlastdate']<86000)
				{
					$wsent=true;
					$response=self::sendMessage($channel_id,$text,$config,$thread->has_attachments?$thread->attachments:[]);
					error_log('Whatsapp response = '.json_encode($response));
					if (empty($response['sent'])) {
						\WhatsApp::log('Error occurred sending WhatsApp message to '.$channel_id.': '.json_encode($response), $conversation->mailbox, true);
					}
					if (isset($response['id']))
					{
						$thread->setMeta('wid',$response['id']);
						$thread->save();
					}
				}
				//send template
				if (!$wsent)
				{
					if (@$thread->meta['wtemplate']!='')
					{
						$wsent=false;
						$response=\WhatsApp::sendTemplate($channel_id,$thread->meta['wtemplate'],$config,$thread->meta);
						if ($response)
						{
							$wsent=true;
							if (isset($response['id']))
							{
								$template=\Whatsapp::getTemplate($thread->meta['wtemplate'],$config);
								$template=json_decode($template->full,true);
								$thread->setMeta('wid',$response['id']);
								$thread->setMeta('wtcat'.strtolower($template['category']),"true");
								$thread->save();
							}
						}
						if (!$wsent)
						{

						}
					}
				}
            } else {
                // Send attachments first.
                if ($thread->has_attachments) {
                    foreach ($thread->attachments as $attachment) {
                        try {
                            self::twilioSendMessage($conversation->mailbox, $channel_id, '', $attachment->url());
                        } catch (\Exception $e) {
                            \WhatsApp::log('Error occurred sending file via WhatsApp to '.$channel_id.': '.$e->getMessage(), $conversation->mailbox, true, self::SYSTEM_TWILIO);
                        }
                    }
                }
                try {
                    self::twilioSendMessage($conversation->mailbox, $channel_id, $text);
                } catch (\Exception $e) {
                    \WhatsApp::log('Error occurred sending WhatsApp message to '.$channel_id.': '.$e->getMessage(), $conversation->mailbox, true, self::SYSTEM_TWILIO);
                }
            }
			$thread->setMeta('channel','whatsapp');
			$thread->save();
			
        }, 20, 2);

		\Eventy::addFilter('voipeintegration.senddefault', function($result, $conversation, $thread, $default_channel) {
			error_log('voipeintegration.senddefault whatsapp');
			error_log('voipeintegration.senddefault whatsapp in '.json_encode($result));
			if ($default_channel==self::CHANNEL)
			{
				error_log('Lets use whatsapp (default)!');
				$result['whatsapp'] = false;
				$result['whatsapp'] = \Eventy::filter('voipeintegration.sendwhatsapp', $result['whatsapp'], $conversation, $thread);
			}
			error_log('voipeintegration.senddefault whatsapp out '.json_encode($result));
			return $result;
        }, 20, 4);

		\Eventy::addFilter('voipeintegration.sendwhatsapp', function($result, $conversation, $thread) {
//            error_log('Test whatsapp message!!!123');
		    $thread = $thread->fresh();
            if ($thread->type!=Thread::TYPE_MESSAGE) return;
			$customer=$conversation->customer;
			$channel_id = false;
			$channel_id = $customer->getChannelId(self::CHANNEL);
			if (!$channel_id) $channel_id = \Eventy::filter('voipeintegration.channel_id', $channel_id, $customer);
			
			if (!$channel_id) {
                error_log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'): customer has no phone number.');
                return false;
            }

            if ($thread->isDraft() || $thread->imported) {
                return false;
            }

            $text = $thread->getBodyAsText();

            $config = $conversation->mailbox->meta[\WhatsApp::DRIVER] ?? [];

            if (self::getSystem($config) == self::SYSTEM_CHATAPI) {
                
				$wsent=false;
				if (isset($customer->meta['wlastdate'])) if (microtime(true)-$customer->meta['wlastdate']<86000)
				{
					$wsent=true;
					$response=self::sendMessage($channel_id,$text,$config,$thread->has_attachments?$thread->attachments:[]);
					error_log('Whatsapp response = '.json_encode($response));
					if (empty($response['sent'])) {
						\WhatsApp::log('Error occurred sending WhatsApp message to '.$channel_id.': '.json_encode($response), $conversation->mailbox, true);
					}
					if (isset($response['id']))
					{
						$thread->setMeta('wid',$response['id']);
						$thread->save();
					}
				}
				//send template
				if (!$wsent)
				{
					if (@$thread->meta['wtemplate']!='')
					{
						$wsent=false;
						$response=\WhatsApp::sendTemplate($channel_id,$thread->meta['wtemplate'],$config,$thread->meta);
						if ($response)
						{	
							$wsent=true;
							if (isset($response['id']))
							{
								$template=\Whatsapp::getTemplate($thread->meta['wtemplate'],$config);
								$template=json_decode($template->full,true);
								$thread->setMeta('wid',$response['id']);
								$thread->setMeta('wtcat'.strtolower($template['category']),"true");
								$thread->save();
							}
						}
						if (!$wsent)
						{

						}
					}
					else
					{
						error_log('ERROR wTemplate empty!');
					}
				}
            } else {
                // // Send attachments first.
                // if ($thread->has_attachments) {
                //     foreach ($thread->attachments as $attachment) {
                //         try {
                //             self::twilioSendMessage($conversation->mailbox, $channel_id, '', $attachment->url());
                //         } catch (\Exception $e) {
                //             \WhatsApp::log('Error occurred sending file via WhatsApp to '.$channel_id.': '.$e->getMessage(), $conversation->mailbox, true, self::SYSTEM_TWILIO);
                //         }
                //     }
                // }
                // try {
                //     self::twilioSendMessage($conversation->mailbox, $channel_id, $text);
                // } catch (\Exception $e) {
                //     \WhatsApp::log('Error occurred sending WhatsApp message to '.$channel_id.': '.$e->getMessage(), $conversation->mailbox, true, self::SYSTEM_TWILIO);
                // }
            }
			$thread->setMeta('channel','whatsapp');
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
                \WhatsApp::log('Can not send a reply to the customer ('.$customer->id.': '.$customer->getFullName().'): customer has no messenger ID.', $conversation->mailbox);
                return;
            }

            // // We send only the last reply.
            // $replies = $replies->sortByDesc(function ($item, $key) {
            //     return $item->id;
            // });
            // $thread = $replies[0];

            if ($thread->isDraft() || $thread->imported) {
                return;
            }

            $text = $thread->getBodyAsText();

            $config = $conversation->mailbox->meta[\WhatsApp::DRIVER] ?? [];

            if (self::getSystem($config) == self::SYSTEM_CHATAPI) {
                //if session is open
				$wsent=false;
				if (isset($customer->meta['wlastdate'])) if (microtime(true)-$customer->meta['wlastdate']<86000)
				{
					$wsent=true;
					$response=self::sendMessage($channel_id,$text,$config,$thread->has_attachments?$thread->attachments:[]);
					error_log('Whatsapp response = '.json_encode($response));
					if (empty($response['sent'])) {
						\WhatsApp::log('Error occurred sending WhatsApp message to '.$channel_id.': '.json_encode($response), $conversation->mailbox, true);
					}
					if (isset($response['id']))
					{
						$thread->setMeta('wid',$response['id']);
						$thread->save();
					}
				}
				//send template
				if (!$wsent)
				{
					if (@$thread->meta['wtemplate']!='')
					{
						$wsent=false;
						$response=\WhatsApp::sendTemplate($channel_id,$thread->meta['wtemplate'],$config,$thread->meta);
						if ($response)
						{
							$wsent=true;
							if (isset($response['id']))
							{
								$template=\Whatsapp::getTemplate($thread->meta['wtemplate'],$config);
								$template=json_decode($template->full,true);
								$thread->setMeta('wid',$response['id']);
								$thread->setMeta('wtcat'.strtolower($template['category']),"true");
								$thread->save();
							}
						}
						if (!$wsent)
						{

						}
					}
				}
            } else {
                // Send attachments first.
                if ($thread->has_attachments) {
                    foreach ($thread->attachments as $attachment) {
                        try {
                            self::twilioSendMessage($conversation->mailbox, $channel_id, '', $attachment->url());
                        } catch (\Exception $e) {
                            \WhatsApp::log('Error occurred sending file via WhatsApp to '.$channel_id.': '.$e->getMessage(), $conversation->mailbox, true, self::SYSTEM_TWILIO);
                        }
                    }
                }
                try {
                    self::twilioSendMessage($conversation->mailbox, $channel_id, $text);
                } catch (\Exception $e) {
                    \WhatsApp::log('Error occurred sending WhatsApp message to '.$channel_id.': '.$e->getMessage(), $conversation->mailbox, true, self::SYSTEM_TWILIO);
                }
            }
			$thread->setMeta('channel','whatsapp');
			$thread->save();
			\DB::insert('insert into billing_statistics (type,month,cnt) values (?, ?, ?) ON DUPLICATE KEY UPDATE `cnt`=`cnt`+?', ["whatsapp_out", date('Y-m'),1,1]);

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
                \WhatsApp::log('Can not send a reply to the customer ('.$customer->id.': '.$customer->getFullName().'): customer has no messenger ID.', $conversation->mailbox);
                return;
            }

            // // We send only the last reply.
            // $replies = $replies->sortByDesc(function ($item, $key) {
            //     return $item->id;
            // });
            // $thread = $replies[0];

            if ($thread->isDraft() || $thread->imported) {
                return;
            }

            $text = $thread->getBodyAsText();

            $config = $conversation->mailbox->meta[\WhatsApp::DRIVER] ?? [];

            if (self::getSystem($config) == self::SYSTEM_CHATAPI) {
                //if session is open
				$wsent=false;
				if (isset($customer->meta['wlastdate'])) if (microtime(true)-$customer->meta['wlastdate']<86000)
				{
					$wsent=true;
					$response=self::sendMessage($channel_id,$text,$config,$thread->has_attachments?$thread->attachments:[]);
					error_log('Whatsapp response = '.json_encode($response));
					if (empty($response['sent'])) {
						\WhatsApp::log('Error occurred sending WhatsApp message to '.$channel_id.': '.json_encode($response), $conversation->mailbox, true);
					}
					if (isset($response['id']))
					{
						$thread->setMeta('wid',$response['id']);
						$thread->save();
					}
				}
				//send template
				if (!$wsent)
				{
					if (@$thread->meta['wtemplate']!='')
					{
						$wsent=false;
						$response=\WhatsApp::sendTemplate($channel_id,$thread->meta['wtemplate'],$config,$thread->meta);
						if ($response)
						{
							$wsent=true;
							if (isset($response['id']))
							{
								$template=\Whatsapp::getTemplate($thread->meta['wtemplate'],$config);
								$template=json_decode($template->full,true);
								$thread->setMeta('wid',$response['id']);
								$thread->setMeta('wtcat'.strtolower($template['category']),"true");
								$thread->save();
							}
						}
						if (!$wsent)
						{

						}
					}
				}
            } else {
                // Send attachments first.
                if ($thread->has_attachments) {
                    foreach ($thread->attachments as $attachment) {
                        try {
                            self::twilioSendMessage($conversation->mailbox, $channel_id, '', $attachment->url());
                        } catch (\Exception $e) {
                            \WhatsApp::log('Error occurred sending file via WhatsApp to '.$channel_id.': '.$e->getMessage(), $conversation->mailbox, true, self::SYSTEM_TWILIO);
                        }
                    }
                }
                try {
                    self::twilioSendMessage($conversation->mailbox, $channel_id, $text);
                } catch (\Exception $e) {
                    \WhatsApp::log('Error occurred sending WhatsApp message to '.$channel_id.': '.$e->getMessage(), $conversation->mailbox, true, self::SYSTEM_TWILIO);
                }
            }
			$thread->setMeta('channel','whatsapp');
			$thread->save();
			\DB::insert('insert into billing_statistics (type,month,cnt) values (?, ?, ?) ON DUPLICATE KEY UPDATE `cnt`=`cnt`+?', ["whatsapp_out", date('Y-m'),1,1]);

        }, 20, 2);

		\Eventy::addAction('conversation.send_reply_save', function($conversation, $request) {

			if ($request->is_whatsapp==1||$conversation->channel == self::CHANNEL)
			{
				$conversation->channel = self::CHANNEL;
				$customer=$conversation->customer;
            	$mailbox = $conversation->mailbox;
				$channel_id = $request->whatsappphone;
				$conversation->save();

			}
        }, 20, 2);

		\Eventy::addAction('workflows.values_custom', function($type, $value, $mode, $and_i, $row_i, $data) {
			if ($type != 'whatsapp') {
				return;
			}
			echo '&nbsp; <a href="'.route('mailboxes.whatsapp.ajax_html', ['action' => 'wf_whatsapp', 'mailbox_id' => $data['mailbox']->id]).'" class="wf-email-modal" data-modal-title="'.__('Edit Whatsapp').'" data-modal-no-footer="true" data-modal-on-show="initWfEmailForm">'. __('Customize').'..</a>
						<input class="form-control" type="hidden" value="'.htmlspecialchars($value).'" name="'.$mode.'['.$and_i.']['.$row_i.'][value]" disabled />';
		}, 20, 6);


		\Eventy::addFilter('workflows.actions_config', function($actions) {
			$actions['dummy']['items']['whatsapp'] = [
				'title' => __('Send Whatsapp'),
                'values_custom' => true,
			];
			return $actions;
        }, 20, 1);

        \Eventy::addFilter('workflow.perform_action', function($performed, $type, $operator, $value, $conversation, $workflow) {
			if ($type == 'whatsapp') {
				error_log('W PERFORMED '.json_encode($performed));
				error_log('W OPERATOR '.json_encode($operator));
				error_log('W VALUE '.json_encode($value));
				error_log('W CONVERSATION ID '.json_encode($conversation->id));
				error_log('W workflow '.json_encode($workflow));
				error_log('W CUSTOMER '.json_encode($conversation->customer));
				// // $value_tags = explode(',', $value);
				// // foreach ($value_tags as $tag_name) {
				// // 	$tag_name = trim($tag_name);
				// // 	Tag::attachByName($tag_name, $conversation->id);
				// // }

				// try {
				// 	$value = json_decode($value ?? '', true);
				// } catch (\Exception $e) {
				// 	return true;
				// }

				// $body = $value['body'] ?? '';
				
				// if ($body) 
				// {
				// 	$mailbox = $conversation->mailbox;
				// 	$customer=$conversation->customer;
				// 	$user = User::where('id', $conversation->user_id)->first();
                //     if ($value['phone']=='')
				// 	{
				// 		$dst = $customer->getChannelId(self::CHANNEL);
				// 		if (!$dst) $dst=$customer->getMainPhoneValue();
				// 	}
				// 	else
				// 	{
				// 		$dst = strip_tags($conversation->replaceTextVars($value['phone'], ['user' => $user]));
				// 		// $dst = $value['phone'];
				// 	}
				// 	if (!$dst) {
				// 		error_log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'): customer has no phone number.');
				// 		return true;
				// 	}

				// 	$user = User::where('id', $conversation->user_id)->first();
                //     $value = $conversation->replaceTextVars($body, ['user' => $user]);
					
				// 	$this::sendSms($mailbox,$dst,$value);
				// }

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
					error_log('Conversation #'.$conversation->number.'. Can not send a reply to the customer (ID '.$customer->id.'; Phone '.$customer->getMainPhoneValue().'): customer has no phone number.');
					return true;
				}
				error_log('DST = '.$dst);
				
				$config = $conversation->mailbox->meta[\WhatsApp::DRIVER] ?? [];
				if (self::getSystem($config) == self::SYSTEM_CHATAPI) 
				{
					if (@$value['whatsapptemplate']!='')
					{
						error_log('WHATSAPPTEMPLATE = '.$value['whatsapptemplate']);
						$wsent=false;

						$wtFiles = [];
						if (isset($value['wt_has_files_example']))
						{
							error_log('BEFORE SETTING');
							$wtOldFiles = [];
							foreach ($value as $vk=>$v) if (strpos($vk,'wt_old_files')===0) $wtOldFiles[]=$v;
						}
						$wtNewFiles = [];
						// if ($request->hasFile('wt_params_IMAGE')) {
						// 	$wtNewFiles = $request->file('wt_params_IMAGE');
						// }else if($request->hasFile('wt_params_DOCUMENT')) {
						// 	$wtNewFiles = $request->file('wt_params_DOCUMENT');
						// }elseif ($request->hasFile('wt_params_VIDEO')) {
						// 	$wtNewFiles = $request->file('wt_params_VIDEO');
						// }

						// if (!empty($wtNewFiles)) {
						// 	foreach ($wtNewFiles as $wtNewFile) {
						// 		if ($wtNewFile->isValid()) { // File successfully downloaded
						// 			$wtFiles['new'][] = $wtNewFile;
						// 		}else {
						// 			$response['msg'] = __('Failed to load files');

						// 			break;
						// 		}
						// 	}
						// }else {
						// 	if (!isset($wtOldFiles)) {
						// 		$response['msg'] = __('Need files for template');
						// 	}
						// }

						error_log('BEFORE OLD');
						if (isset($wtOldFiles)) $wtFiles['old'] = $wtOldFiles;
						error_log('BEFORE SWITCH');
						if (isset($value['wt_file_type']))
						{
							switch ($value['wt_file_type']) {
								case 0:
									if (isset($wtFiles['old'])) {
										for ($x=0;count($wtFiles['old'])-1>=$x;$x++) {
											error_log('Setting wt_params_HEADER_OLD_'.$x);
											$value['wt_params_HEADER_OLD_'.$x]=true;
											error_log('SET');
										}
									}
									break;
								case 1:
									// if (isset($wtFiles['new'])) {
									//     $attachments = \WhatsApp::getFilesLinks($wtFiles['new']);

									//     if (isset($attachments)) {
									//         for ($x=0;count($attachments)-1>=$x;$x++) {
									//             $thread->setMeta('wt_params_HEADER_NEW_'.$x, $attachments[$x]);
									//         }
									//     }
									// }
									break;
							}
						}
						error_log('BEFORE SEND');
						
						$response=\WhatsApp::sendTemplate($dst,$value['whatsapptemplate'],$config,$value);
						if ($response) if (isset($response['id']))
						{
							$created_by_user_id = \Workflow::getUser()->id;
							$template=\WhatsApp::getTemplate($value['whatsapptemplate'],$config);
							error_log('Template = '.json_encode($template));
							error_log('User = '.json_encode($user));
							if ($template)
							{
								$template=json_decode($template->full,true);
								$text='Sent template "'.$template['name'].'": ';
								if (isset($template['components'])) foreach ($template['components'] as $c)
								{
									$componentType = strtolower($c['type']);
									if (in_array($componentType, ['body'])) 
									{
										if (isset($c['text']))
										{
											for ($x=1;strpos($c['text'],'{{'.$x.'}}')!==false;$x++)
											{
												$c['text']=preg_replace('/\\{\\{ '.$x.' \\}\\}/uis',@$value['wt_params_'.$c['type'].'_'.$x]??'test'.$c['type'],$c['text']);
											}
											$text.=$c['text'];
										}
									}
								}
								Thread::createExtended([
										'type' => Thread::TYPE_NOTE,
										'created_by_user_id' => $created_by_user_id,
            							'body' => $text,
										'attachments' => [],
										'imported' => true,
									],
									$conversation,
									$customer
								);
								error_log('process own message');
							}
							else
							{
								Thread::createExtended([
										'type' => Thread::TYPE_NOTE,
										'created_by_user_id' => $created_by_user_id,
            							'body' => 'Sent template',
										'attachments' => [],
										'imported' => true,
									],
									$conversation,
									$customer
								);
								error_log('process own message');
							}
						}
					}
					else
					{
						error_log('NO VALUE WHATSAPPTEMPLATE');
					}
				}
				else
				{
					error_log('SELF SYSTEM NOT CHATAPI');
				}
				return true;
			}
			return $performed;
        }, 20, 6);

		\Eventy::addFilter('voipe.default_channels', function($channels,$mailbox) {
			$settings = $mailbox->meta[self::DRIVER] ?? [];
			error_log('Default Whatsapp before = '.json_encode($channels));
			$channels[]=[
				'type'=>'whatsapp',
				'name'=>'Whatsapp',
				'id'=>self::CHANNEL,
			];
			error_log('Default Whatsapp after = '.json_encode($channels));
			return $channels;
		}, 20, 2);

		\Eventy::addFilter('voipeintegration.channel_id', function($channel_id, $customer) {
            if (!$channel_id) $channel_id = $customer->getChannelId(self::CHANNEL);
            return $channel_id;
        }, 20, 2);
    }

    public static function processIncomingMessage($user_phone, $user_name, $text, $mailbox, $attachments = [])
    {
		error_log('Whatsapp Process Incoming : '.json_encode($user_phone));
        if (in_array($text, self::$skip_messages) && empty($attachments)) {
            return false;
        }

        if (!$user_phone && !$user_name) {
            \WhatsApp::log('Empty user.', $mailbox, true);
            return;
        }

        if (!$user_name) {
            $user_name = $user_phone;
        }

        // Get or creaate a customer.
        $channel = \WhatsApp::CHANNEL;
        $channel_id = $user_phone;

        $customer = Customer::getCustomerByChannel($channel, $channel_id);
		error_log('Whatsapp Customer1 : '.json_encode(@$customer->phones));
        
        // Try to find customer by phone number.
        if (!$customer) {
            // Get first customer by phone number.
            $customer = Customer::findByPhone($channel_id);
            error_log('Whatsapp Customer2 : '.json_encode(@$customer->phones));
        	// For now we are searching for a customer without a channel
            // and link the obtained customer to the channel.
            if ($customer) {
                $customer->addChannel($channel, $channel_id);
				error_log('Whatsapp Customer3 : '.json_encode(@$customer->phones));
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
			error_log('Whatsapp Customer4 : '.json_encode(@$customer->phones));
        
            if (!$customer) {
                \WhatsApp::log('Could not create a customer.', $mailbox, true);
                return;
            }
        }

		$customer->setMeta('wlastdate',microtime(true));
		$customer->save();
		error_log('Whatsapp Customer5 : '.json_encode(@$customer->phones));
        error_log('Whatsapp Customer5 : '.json_encode(@$customer->id));
        
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
			$conversation->save();
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
			$thread->setMeta('channel','whatsapp');
			$thread->save();
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
			$res['thread']->setMeta('channel','whatsapp');
			$res['thread']->save();
        }
	}

    // https://www.twilio.com/docs/whatsapp/api?code-sample=code-send-an-outbound-freeform-whatsapp-message&code-language=curl&code-sdk-version=json
    public static function twilioSendMessage($mailbox, $to, $body, $media_url = '')
    {
        $settings = $mailbox->meta[\WhatsApp::DRIVER] ?? [];

        if (empty($settings['twilio_sid'])
            || empty($settings['twilio_token'])
            || empty($settings['twilio_phone_number'])
        ) {
            throw new \Exception('Can not send a message via Twilio as some parameters are missing. Make sure to enter Account SID, Auth Token and Twilio Phone Number', 1);
            return;
        }

        $api_url = self::TWILIO_API_URL.'/'.$settings['twilio_sid'].'/Messages.json';

        $request_body = [
            'From' => 'whatsapp:+'.self::twilioSanitizePhoneNumber($settings['twilio_phone_number']),
            'Body' => $body,
            'To'   => 'whatsapp:+'.self::twilioSanitizePhoneNumber($to),
        ];
        // https://www.twilio.com/docs/sms/send-messages#include-media-in-your-messages
        if (!empty($media_url)) {
            $request_body['MediaUrl'] = $media_url;
        }

        $ch = curl_init($api_url);

        curl_setopt($ch, CURLOPT_USERPWD, $settings['twilio_sid'] . ":" . $settings['twilio_token']);
        //curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);
        // curl_setopt($ch, CURLOPT_POST, 1);
        // if ($http_method == self::API_METHOD_POST
        //     || $http_method == self::API_METHOD_PUT
        //     || $http_method == self::API_METHOD_PATCH
        // ) {

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request_body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \Helper::setCurlDefaultOptions($ch);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $json_response = curl_exec($ch);

        $response = json_decode($json_response, true);

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch), 1);
        }

        curl_close($ch);

        if (!empty($response['error_code']) || !empty($response['code'])) {
            throw new \Exception('API error: '.json_encode($response), 1);
        }

        return $response;
    }

    public static function twilioSanitizePhoneNumber($phone_number)
    {
        return preg_replace("#[^0-9]#", '', $phone_number);
    }

    // For backward compatibility.
    public static function getSystem($settings)
    {
        $system = $settings['system'] ?? 0;

        if (!$system) {
            return self::SYSTEM_CHATAPI;
        } else {
            return $system;
        }
    }

    public static function getSystemName($system)
    {
        return self::$system_names[$system] ?? self::SYSTEM_CHATAPI_NAME;
    }

    public static function getWebhookUrl($mailbox_id, $system = self::SYSTEM_CHATAPI)
    {
        return route('whatsapp.webhook', [
            'mailbox_id' => $mailbox_id,
            'mailbox_secret' => \WhatsApp::getMailboxSecret($mailbox_id),
            'system' => $system
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
            \WhatsApp::log('Empty user when processing own message.', $mailbox, true);
            return;
        }

		// Do not import messages sent from FreeScout as user messages.
		// if (starts_with($text, '💬 ')) {
		//     return;
		// }
		// Skip if the text is a URL of the attachment from a message sent by
		// a support agent before.
		if (preg_match("#^https?://#", $text)) {
			$url_parts = parse_url(html_entity_decode($text));

			parse_str($url_parts['query'] ?? '', $url_params);

			if (!empty($url_params['id']) && !empty($url_params['token'])) {
				$attachment = Attachment::find($url_params['id']);
				if ($attachment && $attachment->getToken() == $url_params['token']) {
					return;
				}
			}
		}

        // Get or creaate a customer.
        $channel = \WhatsApp::CHANNEL;
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
                \WhatsApp::log('Could not create a customer.', $mailbox, true);
                error_log('cant create customer');
				return;
            }
        }
		// Check if there is already such message from support agent in this conversation.
		$last_user_reply = false;
		if (!is_null($conversation))
		{
			$last_user_reply = Thread::where('conversation_id', $conversation->id)
				//->where('body', $text)
				->where('type', Thread::TYPE_MESSAGE)
				->orderBy('created_at', 'desc')
				->first();
		}
		else
		{
			error_log('WHATSAPP NULL CONVERSATION');
		}
		$text_prepared = html_entity_decode($text);
		$text_prepared = \Helper::htmlToText($text_prepared, false);
		$text_prepared = str_replace('💬 ', '', $text_prepared);
		$text_prepared = preg_replace("#[\r\n\t ]#", '', $text_prepared);
		if ($last_user_reply)
		{
			$last_user_reply_text = preg_replace("#[\r\n\t ]#", '', $last_user_reply->getBodyAsText());
			if ($last_user_reply_text == $text_prepared) return;
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
           //\WhatsApp::log('Could not find customer conversation when importing own message sent directly from WhatsApp on mobile phone as a response to customer. Customer phone number: '.$customer_phone, $mailbox, true);
        }
		error_log('Own msg added');
    }

    public static function getMailboxSecret($id)
    {
        return crc32(config('app.key').$id.'salt'.self::SALT);
    }

    public static function getMailboxVerifyToken($id)
    {
        return crc32(config('app.key').$id.'verify'.self::SALT).'';
    }

    public static function log($text, $mailbox = null, $is_webhook = true, $system = self::SYSTEM_CHATAPI)
    {
        \Helper::log(\WhatsApp::LOG_NAME, '['.self::CHANNEL_NAME.($is_webhook ? ' Webhook' : '').' - '.self::getSystemName($system).'] '.($mailbox ? '('.$mailbox->name.') ' : '').$text);
    }

    public static function setWebhook($config, $mailbox_id, $remove = false)
    {
        $params = [
            'webhookUrl' => self::getWebhookUrl($mailbox_id, self::SYSTEM_CHATAPI)
        ];

        // Not implemented
        if ($remove) {
            $params['webhookUrl'] = 'https://webhook-disabled.doesnotexist';
        }

        $response = self::apiCall($config, self::API_METHOD_SET_WEBOOK, $params,5);

        $output = json_encode($response);

        if ($response && !empty($response['set'])) {
            return [
                'result' => true,
                'msg' => $output,
            ];
        } else {
            return [
                'result' => false,
                'msg' => $output,
            ];
        }
    }

    /**
     * https://my.1msg.io/documentation
     */
    public static function apiCall($config, $method, $params,$timeout=false)
    {
        $channel_id = $config['instance'] ?? '';
        $token = $config['token'] ?? '';

        $url = 'https://api.1msg.io/'.$channel_id.'/'.$method.'?'.http_build_query(['token' => $token]);

        try {
			$options = [
                'json' => $params,
                'proxy' => config('app.proxy'),
            ];
			if ($timeout) $options['read_timeout']=$timeout;
            $response = (new \GuzzleHttp\Client())->request('POST', $url, $options);
        } catch (\Exception $e) {
            return [
                'result' => false,
                'msg' => $e->getMessage(),
            ];
        }

        // https://guzzle3.readthedocs.io/http-client/response.html
        if ($response->getStatusCode() == 200) {
            return \Helper::jsonToArray($response->getBody()->getContents());
        } else {
            return [
                'result' => false,
                'msg' => 'API error: '.$response->getStatusCode(),
            ];
        }
    }

	/**
     * https://my.1msg.io/documentation
     */
    public static function apiCallGet($config, $method, $params, $timeout=false)
    {
        $channel_id = $config['instance'] ?? '';
        $token = $config['token'] ?? '';

        $url = 'https://api.1msg.io/'.$channel_id.'/'.$method.'?'.http_build_query(['token' => $token]);

        try {
			$options = [
                'json' => $params,
                'proxy' => config('app.proxy'),
            ];
			if ($timeout) $options['read_timeout']=$timeout;
            $response = (new \GuzzleHttp\Client())->request('GET', $url, $options);
        } catch (\Exception $e) {
            return [
                'result' => false,
                'msg' => $e->getMessage(),
            ];
        }

        // https://guzzle3.readthedocs.io/http-client/response.html
        if ($response->getStatusCode() == 200) {
            return \Helper::jsonToArray($response->getBody()->getContents());
        } else {
            return [
                'result' => false,
                'msg' => 'API error: '.$response->getStatusCode(),
            ];
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
            __DIR__.'/../Config/config.php' => config_path('whatsapp.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'whatsapp'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/whatsapp');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/whatsapp';
        }, \Config::get('view.paths')), [$sourcePath]), 'whatsapp');
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

    public static function getFilesLinks($files)
    {
        if (count($files)) {
            $attachments = [];
            foreach ($files as $file) {
                // Проверяем, существует ли файл и нет ли ошибок при загрузке
                if ($file->isValid() && $file->getClientSize() > 0) {
                    // Сохраняем файл и получаем его путь
                    $path = $file->store('public'); // Путь к файлу в хранилище public
                    // Получаем URL файла из пути
                    $url = Storage::url($path);
                    // Добавляем URL в массив вложений
                    $attachments[] = $url;
                }
            }
            // Возвращаем массив вложений
            return $attachments;
        }

        return 0;
    }

	public static function sendMessage($channel_id,$text,$config,$attachments=[])
    {
		foreach ($attachments as $attachment) 
		{
			$params = [
				'filename' => $attachment->file_name,
				'body' => $attachment->url(),
				'phone' => $channel_id,
			];
			$response = self::apiCall($config, self::API_METHOD_SEND_FILE, $params);
			if (empty($response['sent'])) {
				error_log('Error occurred sending file via WhatsApp to '.$channel_id);
			}
		}

		$params = [
			'body' => $text,
			'phone' => $channel_id,
		];
		\DB::insert('insert into billing_statistics (type,month,cnt) values (?, ?, ?) ON DUPLICATE KEY UPDATE `cnt`=`cnt`+?', ["whatsapp_out", date('Y-m'),1,1]);

		return self::apiCall($config, self::API_METHOD_SEND, $params);
	}

	public static function updateTemplates($config)
	{
		error_log('updateTemplates config='.json_encode($config));
		$wtemplates = \WhatsApp::apiCallGet($config, \WhatsApp::API_METHOD_TEMPLATES, []);
		$wids=[];
		if (isset($wtemplates['templates'])) foreach ($wtemplates['templates'] as $t) 
		{
			error_log('select id from whatsapp_templates where wid='.json_encode($t['id']));
			$qa=\DB::select('select id from whatsapp_templates where wid=?',[$t['id']]);
			if (count($qa)<1)
			{
				error_log('insert into whatsapp_templates set wid=?, name=?, namespace=?, language=?, components=?, full=? => '.json_encode([$t['id'],$t['name'],$t['namespace'],$t['language'],json_encode($t['components']),json_encode($t)]));
				\DB::insert('insert into whatsapp_templates set wid=?, name=?, namespace=?, language=?, components=?, full=?',[$t['id'],$t['name'],$t['namespace'],$t['language'],json_encode($t['components']),json_encode($t)]);
			}
			$wids[]=$t['id'];
		}
		if (count($wids)>0)
		{
			error_log('DELETE wid'.json_encode($wids));
			\DB::table('whatsapp_templates')->whereNotIn('wid',$wids)->delete();
		}
	}

	public static function getTemplate($wid,$config,$update=false)
	{
		error_log('getTemplate wid='.json_encode($wid).' update='.json_encode($update));
		if ($update) \Whatsapp::updateTemplates($config);
		$qa=\DB::select('select * from whatsapp_templates where wid=?',[$wid]);
		if (count($qa)<1&&!$update)
		{
			\Whatsapp::updateTemplates($config);
			$qa=\DB::select('select * from whatsapp_templates where wid=?',[$wid]);
		}
		if (count($qa)>0) return $qa[0];
		return false;
	}

	public static function getTemplateByName($name,$config,$update=false)
	{
		error_log('getTemplate name='.json_encode($name).' update='.json_encode($update));
		if ($update) \Whatsapp::updateTemplates($config);
		$qa=\DB::select('select * from whatsapp_templates where name=?',[$name]);
		if (count($qa)<1&&!$update)
		{
			\Whatsapp::updateTemplates($config);
			$qa=\DB::select('select * from whatsapp_templates where name=?',[$name]);
		}
		if (count($qa)>0) return $qa[0];
		return false;
	}

	public static function getTemplates($config,$update=false)
	{
		error_log('getTemplates update='.json_encode($update));
		if ($update) \Whatsapp::updateTemplates($config);
		$qa=\DB::select('select * from whatsapp_templates',[]);
		if (count($qa)<1&&!$update)
		{
			\Whatsapp::updateTemplates($config);
			$qa=\DB::select('select * from whatsapp_templates',[]);
		}
		return $qa;
	}

	public static function sendTemplate($channel_id,$wid,$config,$meta)
	{
		// fn sendTemplate "37369799766" | "kAR7Vbty7d8Rmc9JP8RPWT" | {"wtemplate":"kAR7Vbty7d8Rmc9JP8RPWT","wt_params_HEADER_OLD_0":true}
		// Whatsapp request = {"namespace":"c21a75ff_c420_41a8_949b_18e381a36d0d","template":"new_template","language":{"policy":"deterministic","code":"en"},"phone":"37369799766","params":[{"type":"header","parameters":[{"type":"video","video":{"link":"https:\/\/scontent.whatsapp.net\/v\/t61.29466-34\/414673693_1243492073701627_6893878155568196881_n.mp4?ccb=1-7&_nc_sid=8b1bef&_nc_ohc=ZgI4auk-u94Q7kNvgE6YfOs&_nc_ht=scontent.whatsapp.net&edm=AH51TzQEAAAA&oh=01_Q5AaIKv5E-Ox6zgZt8gdUx6CPkYfhI4zHqLNZnqAybSCmGOt&oe=66B0A1E3"}}]}]}
		
		// fn sendTemplate "37369799766" | "kAR7Vbty7d8Rmc9JP8RPWT" | {"phone":"","whatsapptemplate":"kAR7Vbty7d8Rmc9JP8RPWT","wt_need_files":"1"}
		// Whatsapp request = {"namespace":"c21a75ff_c420_41a8_949b_18e381a36d0d","template":"new_template","language":{"policy":"deterministic","code":"en"},"phone":"37369799766","params":[{"type":"header","parameters":[]}]}
		error_log('fn sendTemplate '.json_encode($channel_id).' | '.json_encode($wid).' | '.json_encode($meta));
		$template = \WhatsApp::getTemplate($wid,$config);
		error_log('fn sendTemplate Template = '.json_encode($template));
		if ($template)
		{
			$template=json_decode($template->full,true);
			error_log('Whatsapp Template = '.json_encode($template));
			$params = [
				'namespace' => $template['namespace'],
				'template' => $template['name'],
				'language' => [
					'policy'=>'deterministic',
					'code'=>$template['language'],
				],
				'phone'=>$channel_id,
			];
			if (isset($template['components'])) foreach ($template['components'] as $c)
			{
				$componentType = strtolower($c['type']);
				if (in_array($componentType, ['body', 'footer'])) 
				{
					error_log('Start - '.$componentType.' type; ');
					if (isset($c['text']))
					{
						for ($x=1;strpos($c['text'],'{{'.$x.'}}')!==false;$x++)
						{
							if (!isset($params['params'][$c['type']])) 
							{
								$params['params'][$c['type']]=[
									'type'=>strtolower($c['type']),
									'parameters'=>[]
								];
							}

							$params['params'][$c['type']]['parameters'][]=[
								'type'=>'text',
								'text'=>@$meta['wt_params_'.$c['type'].'_'.$x]??'test'.$c['type'],
							];
						}
					}
				}

				if ($componentType == 'header') 
				{
					error_log('Start - Header type; ');
					$componentFormat = strtolower(@$c['format']);
					if ($componentFormat == 'text' && isset($c['text'])) 
					{
						for ($x=1;strpos($c['text'],'{{'.$x.'}}')!==false;$x++)
						{
							if (!isset($params['params'][$c['type']])) $params['params'][$c['type']]=[
								'type'=>strtolower($c['type']),
								'parameters'=>[]
							];
							$params['params'][$c['type']]['parameters'][]=[
								'type'=>'text',
								'text'=>@$meta['wt_params_'.$c['type'].'_'.$x]??'Test'.$c['type'],
							];
						}
					}
					else
					{
						error_log('Header type - check format; ');
						if (in_array($componentFormat, ['image', 'document', 'video'])) 
						{
							error_log('Header type - '. $componentFormat .' format; ');
							if (!isset($params['params'][$c['type']])) $params['params'][$c['type']]=[
								'type'=>strtolower($c['type']),
								'parameters'=>[]
							];

							if (isset($c['example']['header_handle'])) 
							{
								$couOld = 0;
								foreach ($c['example']['header_handle'] as $key => $link) 
								{
									if (!empty(@$meta['wt_params_HEADER_OLD_'.$key])) 
									{
										$couOld++;
										$params['params'][$c['type']]['parameters'][]=[
											'type'=>$componentFormat,
											$componentFormat => [
												'link' => $link
											],
										];
									}
								}
								error_log('Header type - old files - '. $couOld .'!; ');
							}

							if (!empty(@$meta['wt_params_HEADER_NEW_0'])) 
							{
								error_log('Header type - was NEW files!; ');
								$couNew = 0;
								for ($x=0;$x<=20;$x++) 
								{
									if (empty(@$meta['wt_params_HEADER_NEW_'.$x])) break;
									$couNew++;
									$params['params'][$c['type']]['parameters'][]=[
										'type'=>$componentFormat,
										$componentFormat => [
											'link' => @$meta['wt_params_HEADER_NEW_'.$x]
										],
									];
								}
								error_log('Header type - new files - '. $couNew .'!; ');
							}
						}
					}
				}
			}
			if (isset($params['params'])) $params['params']=array_values($params['params']);
			error_log('Whatsapp request = '.json_encode($params));
			$response = self::apiCall($config, self::API_METHOD_SEND, $params);
			error_log('Whatsapp response = '.json_encode($response));
			\DB::insert('insert into billing_statistics (type,month,cnt) values (?, ?, ?) ON DUPLICATE KEY UPDATE `cnt`=`cnt`+?', ['wtcat'.strtolower($template['category']), date('Y-m'),1,1]);
			\DB::insert('insert into billing_statistics (type,month,cnt) values (?, ?, ?) ON DUPLICATE KEY UPDATE `cnt`=`cnt`+?', ["whatsapp_out", date('Y-m'),1,1]);
			return $response;
		}
		return false;
	}	
}
