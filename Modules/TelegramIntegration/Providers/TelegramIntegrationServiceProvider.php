<?php

namespace Modules\TelegramIntegration\Providers;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\CustomerChannel;
use App\Mailbox;
use App\Thread;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

require_once __DIR__.'/../vendor/autoload.php';

class TelegramIntegrationServiceProvider extends ServiceProvider
{
    const DRIVER = 'telegram';

    // Communication channel.
    // The value must be between 1 and 255. Official modules use channel numbers below 100.
    // Non-official - above 100. To avoid conflicts with other modules contact FreeScout Team
    // to get your Channel number: https://freescout.net/contact-us/
    const CHANNEL = 11;
    const CHANNEL_NAME = 'Telegram';

    const LOG_NAME = 'telegram_integration';
    const SALT = 'o3uDxS3hd';

    public static $skip_messages = [
        '%%%_IMAGE_%%%',
        '%%%_VIDEO_%%%',
        '%%%_FILE_%%%',
        '%%%_AUDIO_%%%',
        '%%%_LOCATION_%%%',
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
        // Add item to the mailbox menu
        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('telegramintegration::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 35);

        \Eventy::addFilter('menu.selected', function($menu) {
            $menu['telegram'] = [
                'mailboxes.telegram.settings',
            ];
            return $menu;
        });

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
        });

        \Eventy::addAction('chat_conversation.send_reply', function($conversation, $replies, $customer) {
            if ($conversation->channel != self::CHANNEL) {
                return;
            }

            $channel_id = $customer->getChannelId(self::CHANNEL);

            if (!$channel_id) {
                \TelegramIntegration::log('Can not send a reply to the customer ('.$customer->id.': '.$customer->getFullName().'): customer has no messenger ID.', $conversation->mailbox);
                return;
            }
            $driver_class = self::getDriverClass();

            $botman = \TelegramIntegration::getBotman($conversation->mailbox, null, false);

            if (!$botman) {
                return;
            }

            // We send only the last reply.
            $replies = $replies->sortByDesc(function ($item, $key) {
                return $item->id;
            });
            $thread = $replies[0];

            // If thread is draft, it means it has been undone
            $thread = $thread->fresh();
            
            if ($thread->isDraft()) {
                return;
            }

            //$botman->typesAndWaits(2);
            
            $text = $thread->getBodyAsText();

            // Attachments.
            // Some drivers can not send message with attachment https://github.com/botman/driver-facebook/issues/65
            if ($thread->has_attachments) {
                foreach ($thread->attachments as $attachment) {
                    
                    $botman_attachment = null;

                    switch ($attachment->type) {
                        case Attachment::TYPE_IMAGE:
                            $botman_attachment = new \BotMan\BotMan\Messages\Attachments\Image($attachment->url(), [
                                'custom_payload' => true,
                            ]);
                            break;
                        case Attachment::TYPE_VIDEO:
                            $botman_attachment = new \BotMan\BotMan\Messages\Attachments\Video($attachment->url(), [
                                'custom_payload' => true,
                            ]);
                            break;
                        case Attachment::TYPE_AUDIO:
                            $botman_attachment = new \BotMan\BotMan\Messages\Attachments\Audio($attachment->url(), [
                                'custom_payload' => true,
                            ]);
                            break;
                        default:
                            // $attachment_url = $attachment->url();
                            // $text .= "\n\n[".\Helper::remoteFileName($attachment_url)."] \n".$attachment_url."";
                            $botman_attachment = new \BotMan\BotMan\Messages\Attachments\File($attachment->url(), [
                                'custom_payload' => true,
                            ]);
                            break;
                    }

                    if ($botman_attachment) {
                        $message = \BotMan\BotMan\Messages\Outgoing\OutgoingMessage::create('')->withAttachment($botman_attachment);
                        $botman->say($message, $channel_id, $driver_class);
                    }
                }
            }

            $botman->say($text, $channel_id, $driver_class);

        }, 20, 3);
    }


    public static function getDriverClass()
    {
        return \BotMan\Drivers\Telegram\TelegramDriver::class;
    }

    public static function getBotman($mailbox, $request = null, $is_webhook = false)
    {
        $driver_config = $mailbox->meta[self::DRIVER] ?? [];

        if (empty($driver_config['enabled']) || !(int)$driver_config['enabled'] || empty($driver_config['token'])) {
            \TelegramIntegration::log(self::CHANNEL_NAME.' is not configured for this mailbox.', $mailbox, $is_webhook);
            return false;
        }
        
        \BotMan\BotMan\Drivers\DriverManager::loadDriver(\BotMan\Drivers\Telegram\TelegramDriver::class);
        \BotMan\BotMan\Drivers\DriverManager::loadDriver(\BotMan\Drivers\Telegram\TelegramAudioDriver::class);
        \BotMan\BotMan\Drivers\DriverManager::loadDriver(\BotMan\Drivers\Telegram\TelegramFileDriver::class);
        \BotMan\BotMan\Drivers\DriverManager::loadDriver(\BotMan\Drivers\Telegram\TelegramPhotoDriver::class);
        \BotMan\BotMan\Drivers\DriverManager::loadDriver(\BotMan\Drivers\Telegram\TelegramLocationDriver::class);
        \BotMan\BotMan\Drivers\DriverManager::loadDriver(\BotMan\Drivers\Telegram\TelegramVideoDriver::class);

        return \BotMan\BotMan\BotManFactory::create([
            self::DRIVER => $driver_config
        ], new \BotMan\BotMan\Cache\LaravelCache());
    }

    public static function processIncomingMessage($bot, $text, $mailbox, $files = [])
    {
        if ($bot->isBot()) {
            return false;
        }

        if (in_array($text, self::$skip_messages) && empty($files)) {
            return false;
        }

        $messenger_user = $bot->getUser();

        // Skip user agent message.
        if (!$messenger_user) {
            \TelegramIntegration::log('Empty user.', $mailbox, true);
            return;
        }

        // Get or creaate a customer.
        $channel_id = $messenger_user->getId();
        if (!$channel_id) {
            \TelegramIntegration::log('User has no ID: '.json_encode($messenger_user->getInfo()).'. Check App Logs', $mailbox, true);
            return;
        }
        $channel = \TelegramIntegration::CHANNEL;

        $customer_info = null;

        $customer = Customer::getCustomerByChannel($channel, $channel_id);

        $matched_by_social_profile = false;
        $new = false;

        // Telegram Bot API allows to get phone number only via special button,
        // so we can't search customers by phone number here.
        // Try to find customer by username.
        if (!$customer) {
            $customer_info = $messenger_user->getInfo();

            if (!empty($customer_info['user']['username'])) {
                $customer = Customer::findCustomersBySocialProfile(
                    Customer::SOCIAL_TYPE_TELEGRAM,
                    $customer_info['user']['username'],
                    self::CHANNEL
                )->first();
                
                if ($customer) {
                    $customer->addChannel($channel, $channel_id);
                    $matched_by_social_profile = true;
                }
            }
        }

        if (!$customer) {
            if (!$customer_info) {
                $customer_info = $messenger_user->getInfo();
            }
            $customer_data = [
                // These two lines will add a record to customer_channel via observer.
                'channel' => $channel,
                'channel_id' => $channel_id,

                'first_name' => $messenger_user->getFirstName() ?: $channel_id,
                'last_name' => $messenger_user->getLastName(),
                'social_profiles' => Customer::formatSocialProfiles([[
                    'type' => Customer::SOCIAL_TYPE_TELEGRAM,
                    'value' => $messenger_user->getUsername(),
                ]])
            ];

            // Social networks.
            $email = $customer_info['user']['email'] ?? $customer_info['user']['mail'] ?? '';
            if ($email) {
                $customer = Customer::create($email, $customer_data);
            } else {
                $customer = Customer::createWithoutEmail($customer_data);
            }
            if (!$customer) {
                \TelegramIntegration::log('Could not create a customer.', $mailbox, true);
                return;
            }
            $new = true;
        }
        // Get Telegram user photo.
        if ($matched_by_social_profile || $new) {
            $photo_url = '';
            try {
                $photo_url = $bot->getUserPhoto($messenger_user->getId());
            } catch (\Exception $e) {
                // Do nothing.
            }
            
            if ($photo_url) {
                if ($customer->setPhotoFromRemoteFile($photo_url)) {
                    $customer->save();
                }
            }
        }
        //$bot->reply('Customer ID:'.$customer->id);

        // Get last customer conversation or create a new one.
        $conversation = Conversation::where('mailbox_id', $mailbox->id)
            ->where('customer_id', $customer->id)
            ->where('channel', $channel)
            ->orderBy('created_at', 'desc')
            ->first();

        $attachments = [];

        if (count($files)) {
            
            foreach ($files as $file) {
                // https://github.com/botman/driver-telegram/issues/102
                if (method_exists($file, 'getTitle') && $file->getTitle()) {
                    if ($text == __('Image(s)')) {
                        $text = '';
                    } else {
                        $text .= "\n\n";
                    }
                    $text .= $file->getTitle();
                }
                // if ($file->getPayload()) {
                //     $text .= ": ".json_encode($file->getPayload());
                // }
                $file_url = $file->getUrl();
                if (!$file_url) {
                    continue;
                }
                $attachments[] = [
                    'file_name' => \Helper::remoteFileName($file_url),
                    'file_url' => $file_url,
                ];
            }
        }

        $text = nl2br($text);

        if ($conversation) {
            // Create thread in existing conversation.
            $queries = Thread::createExtended([
                    'type' => Thread::TYPE_CUSTOMER,
                    'customer_id' => $customer->id,
                    'body' => $text,
                    'attachments' => $attachments,
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
                    'type' => Thread::TYPE_CUSTOMER,
                    'customer_id' => $customer->id,
                    'body' => $text,
                    'attachments' => $attachments,
                ]],
                $customer
            );
        }
        // Auto reply to /start.
//        if ($text == '/start' && !empty($mailbox->meta[\TelegramIntegration::DRIVER]['auto_reply'])) {
//            $bot->reply($mailbox->meta[\TelegramIntegration::DRIVER]['auto_reply']);
//        }
    }


    public static function getMailboxSecret($id)
    {
        return crc32(config('app.key').$id.'salt'.self::SALT);
    }

    public static function getMailboxVerifyToken($id)
    {
        return crc32(config('app.key').$id.'verify'.self::SALT).'';
    }

    public static function log($text, $mailbox = null, $is_webhook = true)
    {
        \Helper::log(\TelegramIntegration::LOG_NAME, '['.self::CHANNEL_NAME.($is_webhook ? ' Webhook' : '').'] '.($mailbox ? '('.$mailbox->name.') ' : '').$text);
    }

    public static function setWebhook($token, $mailbox_id, $remove = false)
    {
        $url = 'https://api.telegram.org/bot'.$token.'/setWebhook';

        if (!$remove) {
            $url .= '?url='.route('telegram.webhook', ['mailbox_id' => $mailbox_id, 'mailbox_secret' => \TelegramIntegration::getMailboxSecret($mailbox_id)]);
        }

        try {
            $output = file_get_contents($url);
            $response = json_decode($output);
        } catch (\Exception $e) {
            if (strstr($e->getMessage(), '404 Not Found')) {
                return [
                    'result' => false,
                    'msg' => __('Invalid Bot API Token'),
                ];
            }
            return [
                'result' => false,
                'msg' => $e->getMessage(),
            ];
        }

        if ($response
            && isset($response->ok) &&  $response->ok == true 
            && isset($response->result) && $response->result == true
        ) {
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
            __DIR__.'/../Config/config.php' => config_path('telegramintegration.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'telegramintegration'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/telegramintegration');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/telegramintegration';
        }, \Config::get('view.paths')), [$sourcePath]), 'telegramintegration');
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
}
