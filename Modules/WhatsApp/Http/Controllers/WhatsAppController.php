<?php

namespace Modules\WhatsApp\Http\Controllers;

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

use Modules\AIAssistant\Jobs\ProcessAIResponse;
use Modules\AIAssistant\Models\Ticket;

class WhatsAppController extends Controller
{
    public function webhooks(Request $request, $mailbox_id, $mailbox_secret, $system = \WhatsApp::SYSTEM_CHATAPI)
    {
        if (class_exists('Debugbar')) {
            \Debugbar::disable();
        }

        $mailbox = Mailbox::find($mailbox_id);

        if (
            !$mailbox || \WhatsApp::getMailboxSecret($mailbox_id) != $request->mailbox_secret
        ) {
            \WhatsApp::log('Incorrect webhook URL: ' . url()->current(), $mailbox ?? null, true, $system);
            abort(404);
        }

        $settings = $mailbox->meta[\WhatsApp::DRIVER] ?? [];

        if (empty($settings['enabled'])) {
            \WhatsApp::log('Webhook triggered but WhatsApp integration is not enabled.', $mailbox ?? null, true, $system);
            abort(404);
        }



        if (\WhatsApp::getSystem($settings) != $system) {
            \WhatsApp::log('Webhook triggered by ' . \WhatsApp::getSystemName($system) . ', but currently ' . \WhatsApp::getSystemName(\WhatsApp::getSystem($settings)) . ' is enabled.', $mailbox ?? null, true, $system);
            abort(404);
        }

        $data = $request->all();

        error_log('Whatsapp IN Data = ' . json_encode($data));
        // 1msg.io.
        if ($system == \WhatsApp::SYSTEM_CHATAPI) {

            if (isset($data['ack'])) {
                foreach ($data['ack'] as $a) {
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
                        $thread->setMeta('wstatus', $a['status']);
                        $thread->save();
                    }
                }
            } else {

                if (empty($data['messages'])) {
                    // Do not log: {"ack":[{"id":"gBGHVRmZUZdzLwIJJ3C5ey3WeP8i","chatId":"5519995197732@c.us","status":"read"}],"channelId":457431}
                    if (empty($data['ack'])) {
                        \WhatsApp::log('Webhook triggered but invalid data received: ' . json_encode($data), $mailbox, true, $system);
                    }
                    abort(404);
                }

                //\Log::error('Webhook data: '.json_encode($data));
                error_log('Whatsapp data: ' . json_encode($data));



                foreach ($data['messages'] as $message) {

                    $sender_phone = $message['author'] ?? '';
                    $sender_phone = preg_replace("/@.*/", '', $sender_phone);
                    $sender_name = $message['senderName'] ?? '';

                    $from_customer = true;
                    $customer_phone = $message['chatId'] ?? '';
                    $customer_phone = preg_replace("/@.*/", '', $customer_phone);
                    $user = null;

                    if (!empty($message['fromMe'])) {
                        // Message from user.
                        $from_customer = false;

                        if (!$customer_phone) {
                            \WhatsApp::log('Could not import your own message sent directly from WhatsApp on mobile phone as a response to customer. Customer phone number which should be passed to Webhook in chatId parameter is empty. Webhook data: ' . json_encode($data), $mailbox, true, $system);
                            return;
                        }

                        // Try to find user by phone number.
                        $user = User::where('phone', $sender_phone)->first();
                        if (!$user) {
                            //\WhatsApp::log('Could not import your own message sent directly from WhatsApp on mobile phone as a response to customer. Could not find the user with the following phone number: '.$sender_phone.'. Make sure to set this phone number to one of the users in FreeScout. Webhook data: '.json_encode($data), $mailbox, true, $system);
                            \WhatsApp::log('Could not import your own message sent as a response to a customer. Could not find the user with the following phone number: ' . $sender_phone . '. Make sure to set this phone number to one of the users in FreeScout. Webhook data: ' . json_encode($data), $mailbox, true, $system);
                            return;
                        }
                    }

                    $attachments = [];
                    $body = '';
                    $message_type = $message['type'] ?? '';

                    switch ($message_type) {
                        case \WhatsApp::API_MESSAGE_TYPE_IMAGE:
                        case \WhatsApp::API_MESSAGE_TYPE_VIDEO:
                            if (empty($message['caption'])) {
                                if ($message_type == \WhatsApp::API_MESSAGE_TYPE_IMAGE) {
                                    $body = __('Image');
                                } else {
                                    $body = __('Video');
                                }
                            }

                            $attachments[] = [
                                'file_name' => \Helper::remoteFileName($message['body']),
                                'file_url' => $message['body']
                            ];
                            break;

                        case \WhatsApp::API_MESSAGE_TYPE_LOCATION:
                            $body = __('Location') . ': ' . $message['body'];
                            break;

                        case \WhatsApp::API_MESSAGE_TYPE_DOCUMENT:
                            $body = __('Document');
                            $attachments[] = [
                                'file_name' => $message['caption'] ?? \Helper::remoteFileName($message['body']),
                                'file_url' => $message['body']
                            ];
                            $message['caption'] = '';
                            break;

                        case \WhatsApp::API_MESSAGE_TYPE_AUDIO:
                        case \WhatsApp::API_MESSAGE_TYPE_VOICE:
                            $body = __('Audio');
                            $attachments[] = [
                                'file_name' => \Helper::remoteFileName($message['body']),
                                'file_url' => $message['body']
                            ];
                            break;

                        case \WhatsApp::API_MESSAGE_TYPE_CONTACT:
                            $body = __('Contact') . "\n\n" . $message['body'];
                            break;

                        default:
                            //case \WhatsApp::API_MESSAGE_TYPE_TEXT:
                            //case \WhatsApp::API_MESSAGE_TYPE_INTERACTIVE:
                            //case \WhatsApp::API_MESSAGE_TYPE_STICKER:
                            if (!empty($message['body']) && !is_array($message['body'])) {
                                $body = $message['body'] ?? '';
                            }
                    }

                    $body = htmlspecialchars($body);

                    $body = nl2br($body);

                    if (!empty($message['caption'])) {
                        $body = $body . "<br><br>" . $message['caption'];
                    }

                    // 1msg.io sends only quotedMsgId, but we keep this just in case.
                    if (!empty($message['quotedMsgBody'])) {
                        $body = '<blockquote>' . $message['quotedMsgBody'] . '</blockquote><br>' . $body;
                    }

                    // There is no such parameter in 1msg.io, but we keep it just in case.
                    if (!empty($message['file']) && empty($attachments)) {
                        $attachments[] = [
                            'file_name' => \Helper::remoteFileName($message['file']),
                            'file_url' => $message['file']
                        ];
                    }

                    if ($from_customer) {
                        \DB::insert('insert into billing_statistics (type,month,cnt) values (?, ?, ?) ON DUPLICATE KEY UPDATE `cnt`=`cnt`+?', ["whatsapp_in", date('Y-m'), 1, 1]);
                        \WhatsApp::processIncomingMessage($sender_phone, $sender_name, $body, $mailbox, $attachments);
                    } else {
                        // From user.
                        \WhatsApp::processOwnMessage($user, $customer_phone, $body, $mailbox, $attachments, $message['chatName'] ?? $customer_phone);
                    }
                }

                // $botman->hears('(.*)', function ($bot, $text) use ($mailbox) {
                //     \Log::error('text: '.$text);
                //     \WhatsApp::processIncomingMessage($bot, $text, $mailbox);
                // });

                // $botman->receivesFiles(function($bot, $files) use ($mailbox) {

                //     \WhatsApp::processIncomingMessage($bot, __('File(s)'), $mailbox, $files);

                //     // foreach ($files as $file) {

                //     //     $url = $file->getUrl(); // The direct url
                //     //     $payload = $file->getPayload(); // The original payload
                //     // }
                // });

                // $botman->receivesImages(function($bot, $images) use ($mailbox) {
                //     \WhatsApp::processIncomingMessage($bot, __('Image(s)'), $mailbox, $images);

                //     // foreach ($images as $image) {

                //     //     $url = $image->getUrl(); // The direct url
                //     //     $title = $image->getTitle(); // The title, if available
                //     //     $payload = $image->getPayload(); // The original payload
                //     // }
                // });

                // $botman->receivesVideos(function($bot, $videos) use ($mailbox) {
                //     \WhatsApp::processIncomingMessage($bot, __('Video(s)'), $mailbox, $videos);
                //     // foreach ($videos as $video) {

                //     //     $url = $video->getUrl(); // The direct url
                //     //     $payload = $video->getPayload(); // The original payload
                //     // }
                // });

                // $botman->receivesAudio(function($bot, $audios) use ($mailbox) {
                //     \WhatsApp::processIncomingMessage($bot, __('Audio'), $mailbox, $audios);
                //     // foreach ($audios as $audio) {

                //     //     $url = $audio->getUrl(); // The direct url
                //     //     $payload = $audio->getPayload(); // The original payload
                //     // }
                // });

                // $botman->receivesLocation(function($bot, $location) use ($mailbox) {
                //     \WhatsApp::processIncomingMessage($bot, __('Location: '.$location->getLatitude().','.$location->getLongitude()), $mailbox);
                //     // $lat = $location->getLatitude();
                //     // $lng = $location->getLongitude();
                // });

                //$botman->listen();
            }
        }

        // Twilio.
        if ($system == \WhatsApp::SYSTEM_TWILIO) {
            //\WhatsApp::log('Data: '.json_encode($data), $mailbox, true, $system);
            // \WhatsApp::log('settings: '.json_encode($settings), $mailbox, true, $system);

            // Check SID.
            if (($settings['twilio_sid'] ?? '') != $request->input('AccountSid')) {
                \WhatsApp::log('Incorrect Account SID received in webhook: ' . $request->input('AccountSid'), $mailbox, true, $system);
                abort(404);
            }
            $customer_phone = str_replace('whatsapp:+', '', $data['From'] ?? '');
            $customer_name = $data['ProfileName'] ?? '';
            // Attachment may be sent without description.
            $body = $data['Body'] ?? '';

            if (!empty($data['Latitude']) && !empty($data['Longitude'])) {
                $body = $data['Latitude'] . ',' . $data['Longitude'];
            }

            if (!$body) {
                $body = '&nbsp;';
            }

            // Build files array.
            $files = [];
            $files_count = (int)$request->input('NumMedia');

            for ($i = 0; $i < $files_count; $i++) {
                if (!empty($data["MediaUrl" . $i])) {

                    $file_url = $data["MediaUrl" . $i] ?? '';

                    // https://github.com/freescout-helpdesk/freescout/issues/3906
                    // To avoid https://www.twilio.com/docs/api/errors/20003
                    if (strstr($file_url, '/Accounts/')) {
                        // Get media files with HTTP Basic Authentication enabled in Twilio
                        // https://www.twilio.com/docs/sms/tutorials/how-to-receive-and-download-images-incoming-mms/php-laravel
                        if (!empty($settings['twilio_sid']) && !empty($settings['twilio_token'])) {
                            $file_url = str_replace('https://', 'https://' . $settings['twilio_sid'] . ':' . $settings['twilio_token'] . '@', $file_url);
                        }
                    }
                    $file_data = [
                        'file_name' => \Helper::remoteFileName($file_url),
                        'file_url'  => $file_url,
                        'mime_type' => $data["MediaContentType" . $i] ?? $data["MediaContentType"] ?? '',
                    ];

                    // MediaUrl returns a real URL in a "Location:" header.
                    // Try to get mime type by following redirects.
                    $last_file_url = $file_url;
                    $last_content_type = '';
                    for ($i = 0; $i < 10; $i++) {
                        $headers = get_headers($last_file_url);
                        $has_redirect = false;
                        foreach ($headers as $header) {
                            if (preg_match("#^location:(.*)#i", $header, $m) && !empty($m[1])) {
                                $last_file_url = trim($m[1]);
                                $has_redirect = true;
                            }
                            if (preg_match("#^content-type:(.*)#i", $header, $m) && !empty($m[1])) {
                                $last_content_type = trim($m[1]);
                            }
                        }
                        if (!$has_redirect) {
                            break;
                        }
                    }
                    if ($last_content_type) {
                        if (preg_match("#/(.*)#", $last_content_type, $m) && !empty($m[1])) {
                            $file_data['mime_type'] = $last_content_type;
                            if (!strstr($file_data['file_name'], '.')) {
                                $file_data['file_name'] = $file_data['file_name'] . '.' . $m[1];
                            }
                        }
                    }
                    if ($last_file_url) {
                        $file_data['file_url'] = $last_file_url;
                    }

                    $files[] = $file_data;
                }
            }

            //\WhatsApp::log('data: '.json_encode($data).'; files: '.json_encode($files), $mailbox, true, $system);

            \WhatsApp::processIncomingMessage($customer_phone, $customer_name, $body, $mailbox, $files);
        }

        $ticket = new Ticket;
        $ticket->message = $request->messages[0]['body'];
        ProcessAIResponse::execute($ticket);
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

        $settings = $mailbox->meta[\WhatsApp::DRIVER] ?? [];
        // $response = \WhatsApp::apiCallGet($settings, \WhatsApp::API_METHOD_TEMPLATES,[]);
        // error_log('response = '.json_encode($response));

        return view('whatsapp::settings', [
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

        $settings = $request->settings;
        $settings_prev = $mailbox->meta[\WhatsApp::DRIVER] ?? [];

        $webhooks_enabled = (int)($mailbox->meta[\WhatsApp::DRIVER]['enabled'] ?? 0);

        $settings['enabled'] = (int)($settings['enabled'] ?? 0);
        $settings['initiate_enabled'] = (int)($settings['initiate_enabled'] ?? 0);

        // Try to add a webhook.
        if (
            \WhatsApp::getSystem($settings) == \WhatsApp::SYSTEM_CHATAPI
            && (!$webhooks_enabled || \WhatsApp::getSystem($settings_prev) != \WhatsApp::SYSTEM_CHATAPI)
            && (int)$settings['enabled']
        ) {
            $result = \WhatsApp::setWebhook($settings, $mailbox->id);

            if (!$result['result']) {
                $settings['enabled'] = false;
                \Session::flash('flash_error', '(' . \WhatsApp::SYSTEM_CHATAPI_NAME . ') ' . __('Error occurred setting up a Whatsapp webhook') . ': ' . $result['msg']);
            }
        }
        // Remove webhook.
        if ((\WhatsApp::getSystem($settings) == \WhatsApp::SYSTEM_CHATAPI
                || \WhatsApp::getSystem($settings_prev) == \WhatsApp::SYSTEM_CHATAPI
            )
            && $webhooks_enabled
            && !(int)$settings['enabled']
        ) {
            \Log::error('remove hook');
            $result = \WhatsApp::setWebhook($settings, $mailbox->id, true);

            if (!$result['result']) {
                \Session::flash('flash_error', '(' . \WhatsApp::SYSTEM_CHATAPI_NAME . ') ' . __('Error occurred removing a Whatsapp webhook') . ': ' . $result['msg']);
            }
        }

        $mailbox->setMetaParam(\WhatsApp::DRIVER, $settings);
        $mailbox->save();

        \Session::flash('flash_success_floating', __('Settings updated'));

        return redirect()->route('mailboxes.whatsapp.settings', ['mailbox_id' => $mailbox_id]);
    }

    public function templates($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);

        if (!auth()->user()->isAdmin()) {
            \Helper::denyAccess();
        }

        $settings = $mailbox->meta[\WhatsApp::DRIVER] ?? [];
        $ta = \WhatsApp::getTemplates($settings, true);
        \WhatsApp::log('response = ' . json_encode($ta), $mailbox, true, \WhatsApp::SYSTEM_CHATAPI);
        $templates = [];
        foreach ($ta as $tk => $tv) $templates[$tk] = json_decode($tv->full, true);

        $needWhatsappSettings = false;
        if (empty($mailbox->meta['whatsapp']['token'])) $needWhatsappSettings = true;

        return view('whatsapp::templates', [
            'mailbox'   => $mailbox,
            'settings'   => $settings,
            'templates' => $templates,
            'needSettings' => $needWhatsappSettings
        ]);
    }

    public function createTemplate(Request $request)
    {

        $templateTree = [
            'name',
            'category' => [
                'MARKETING',
                'UTILITY',
                'AUTHENTICATION'
            ],
            'components' => [
                'type' => [
                    'BODY',
                    'HEADER',
                    'FOOTER',
                    'BUTTONS'
                ],
                'format' => [
                    'TEXT',
                    'IMAGE',
                    'DOCUMENT',
                    'VIDEO'
                ],
                'text',
                'example',
                'buttons' => [
                    'type' => [
                        'PHONE_NUMBER',
                        'URL',
                        'QUICK_REPLY'
                    ],
                    'text',
                    'url',
                    'phone_number',
                    'example'
                ]
            ],
            'language' => [
                "af" => "Afrikaans",
                "sq" => "Albanian",
                "ar" => "Arabic",
                "az" => "Azerbaijani",
                "bn" => "Bengali",
                "bg" => "Bulgarian",
                "ca" => "Catalan",
                "zh_CN" => "Chinese (Simplified)",
                "zh_HK" => "Chinese (Hong Kong)",
                "zh_TW" => "Chinese (Traditional)",
                "hr" => "Croatian",
                "cs" => "Czech",
                "da" => "Danish",
                "nl" => "Dutch",
                "en" => "English",
                "en_GB" => "English (UK)",
                "en_US" => "English (US)",
                "et" => "Estonian",
                "fil" => "Filipino",
                "fi" => "Finnish",
                "fr" => "French",
                "de" => "German",
                "el" => "Greek",
                "gu" => "Gujarati",
                "he" => "Hebrew",
                "hi" => "Hindi",
                "hu" => "Hungarian",
                "id" => "Indonesian",
                "ga" => "Irish",
                "it" => "Italian",
                "ja" => "Japanese",
                "kn" => "Kannada",
                "kk" => "Kazakh",
                "ko" => "Korean",
                "lo" => "Lao",
                "lv" => "Latvian",
                "lt" => "Lithuanian",
                "mk" => "Macedonian",
                "ms" => "Malay",
                "mr" => "Marathi",
                "nb" => "Norwegian (Bokmål)",
                "fa" => "Persian",
                "pl" => "Polish",
                "pt_BR" => "Portuguese (Brazil)",
                "pt_PT" => "Portuguese (Portugal)",
                "pa" => "Punjabi",
                "ro" => "Romanian",
                "ru" => "Russian",
                "sr" => "Serbian",
                "sk" => "Slovak",
                "sl" => "Slovenian",
                "es" => "Spanish",
                "es_AR" => "Spanish (Argentina)",
                "es_ES" => "Spanish (Spain)",
                "es_MX" => "Spanish (Mexico)",
                "sw" => "Swahili",
                "sv" => "Swedish",
                "ta" => "Tamil",
                "te" => "Telugu",
                "th" => "Thai",
                "tr" => "Turkish",
                "uk" => "Ukrainian",
                "ur" => "Urdu",
                "uz" => "Uzbek",
                "vi" => "Vietnamese"
            ]
        ];

        return view('whatsapp::create_template', [
            'templateTree' => $templateTree,
            'mailbox' =>  $request->input('mailbox')
        ]);
    }
    public function createTemplateSave(Request $request)
    {
        $filesErr = false;
        $data = $request->all();

        $validator = Validator::make($data, [
            'components.body.text' => 'required',
            'components.footer.text' => 'nullable|string',
            'name' => [
                'string',
                'required',
                function ($attribute, $value, $fail) {
                    if (empty($value)) {
                        $fail(__('Fill in the Template Name'));
                    } elseif (!preg_match('/^[a-z0-9_]+$/i', $value)) {
                        $fail(__('Invalid Template name'));
                    }
                }
            ],
            'components.files' => '',
        ], [], [
            'components.body.text' => 'Body Text',
            'name' => '"Template Name"',
        ]);

        if (strtoupper($data['components']['header']['format']) != 'TEXT' && empty($request->file('components.files'))) {
            $filesErr = true;
        }

        if ($validator->fails() || $filesErr) {
            if ($filesErr)
                $validator->getMessageBag()->add('components.files', 'Prepare materials');

            return redirect()->route('whatsapp.create_template', ['mailbox' => $data['mailbox']])
                ->withErrors($validator)
                ->withInput();
        }

        $params = [
            "name" => $data['name'],
            "category" => $data['category'],
            "language" => $data['language'],
            "components" => []
        ];
        foreach ($data['components'] as $type => $component) {
            $item = [];
            switch ($type) {
                case 'body':
                    if (isset($component['text'])) {
                        $item['type'] = $type;
                        $item['text'] = trim($component['text']);
                    }
                    break;
                case 'header':
                    $attachments = false;

                    $item['type'] = $type;
                    $item['format'] = $component['format'];

                    if (strtoupper($component['format']) == 'TEXT') {
                        if (!empty($component['text'])) {
                            $item['text'] = $component['text'];
                        } else {
                            $item = [];
                        }
                    } else {
                        $attachments = $this->getFilesLinks($request->file('components.files'));

                        if (!$attachments) {
                            $validator->getMessageBag()->add('components.files', 'Could not save materials');
                            return redirect()->route('whatsapp.create_template', ['mailbox' => $data['mailbox']])
                                ->withErrors($validator)
                                ->withInput();
                        }

                        $item['example'] = [
                            'header_handle' => $attachments
                        ];
                    }

                    break;
                case 'footer':
                    if (!empty($component['text'])) {
                        $item['type'] = $type;
                        $item['text'] = trim($component['text']);
                    }
                    break;
                case 'buttons':
                    break;
            }

            if (!empty($item['type'])) {
                $params['components'][] = $item;
            }
        }

        $mailbox = Mailbox::findOrFail($data['mailbox']);

        if (!auth()->user()->isAdmin()) {
            \Helper::denyAccess();
        }

        $settings = $mailbox->meta[\WhatsApp::DRIVER] ?? [];
        error_log('Whatsapp request = ' . json_encode($params));
        $response = \WhatsApp::apiCall($settings, \WhatsApp::API_METHOD_TEMPLATE_ADD, $params);
        \WhatsApp::log('response = ' . json_encode($response), $mailbox, true, \WhatsApp::SYSTEM_CHATAPI);

        if (!empty($response)) {
            echo "<pre>";
            print_r($response);
            echo "</pre>";
            exit;

            \Session::flash('flash_error_create_template', __('Failed to create template'));
            return redirect()->route('whatsapp.create_template', ['x_embed' => 1, 'mailbox' => $mailbox->id])
                ->withInput();
        }
        \Whatsapp::updateTemplates($settings);

        \Session::flash('flash_success_create_template', __('New Template saved successfully'));
        return redirect()->route('whatsapp.create_template', ['x_embed' => 1, 'mailbox' => $mailbox->id]);
    }

    public function getMediaId($files, $mailbox)
    {
        $base64 = $this->getBase64Files($files);
        if ($base64 == 'err')
            return 0;

        // $files - 'link' or base64 files with mime data
        // Sending images
        $mailbox = Mailbox::findOrFail($mailbox);
        if (!auth()->user()->isAdmin()) {
            \Helper::denyAccess();
        }

        $settings = $mailbox->meta[\WhatsApp::DRIVER] ?? [];

        $filesID = [];
        $hasErr = false;
        foreach ($base64 as $file) {
            $response = \WhatsApp::apiCall($settings, \WhatsApp::API_METHOD_UPLOAD_MEDIA, ['body' => $file]);
            \WhatsApp::log('response = ' . json_encode($response), $mailbox, true, \WhatsApp::SYSTEM_CHATAPI);

            if (empty($response)) {
                $hasErr = true;
            } else {
                $filesID[] = $response['mediaId'];
            }
        }

        if ($hasErr)
            return 0;

        return $filesID;
    }

    public function getBase64Files($files)
    {
        $arr = [];
        $err = false;
        foreach ($files as $file) {
            if ($file->isValid()) {
                $fileContents = file_get_contents($file->path());
                $base64Encoded = base64_encode($fileContents);
                $dataUri = 'data:' . $file->getMimeType() . ';base64,' . $base64Encoded;

                $arr[] = $dataUri;
            } else {
                $err = true;
            }
        }

        if ($err)
            return 'err';

        return $arr;
    }

    public function removeTemplate()
    {
        $mailbox = Mailbox::findOrFail($_POST['mailbox']);

        if (!auth()->user()->isAdmin()) {
            \Helper::denyAccess();
        }

        $settings = $mailbox->meta[\WhatsApp::DRIVER] ?? [];

        if (strpos($_POST['name'], 'start_template') !== false) {
            return 'error';
        }

        $response = \WhatsApp::apiCall($settings, \WhatsApp::API_METHOD_TEMPLATE_REMOVE, ['name' => $_POST['name']]);
        \WhatsApp::log('response = ' . json_encode($response), $mailbox, true, \WhatsApp::SYSTEM_CHATAPI);
        \WhatsApp::updateTemplates($settings);

        if (empty($response)) {
            return 'success';
        } else {
            return 'error';
        }
    }

    protected function getFilesLinks($files)
    {
        if (count($files)) {
            $attachments = [];
            foreach ($files as $file) {
                // Check if the file exists and if there are any errors while downloading
                if ($file->isValid() && $file->getClientSize() > 0) {
                    // Save the file and get its path
                    $path = $file->store('public'); // Path to the file in the public storage
                    // Get file URL from path
                    $url = Storage::url($path);
                    // Adding the URL to the attachment array
                    $attachments[] = $url;
                }
            }
            // Returning an array of attachments
            return $attachments;
        }

        return 0;
    }

    public function viewTemplate($id)
    {
        $mailbox_id = $_GET['mailbox'];

        $mailbox = Mailbox::findOrFail($mailbox_id);
        $wsettings = $mailbox->meta[\WhatsApp::DRIVER] ?? [];
        $tv = \WhatsApp::getTemplate($id, $wsettings);
        $template = [];
        if ($tv) {
            $template = json_decode($tv->full, true);
        }
        return view('partials/view_whatsapp_template', ['template' => $template]);
    }

    /**
     * Ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        $user = auth()->user();

        switch ($request->action) {
            case 'wf_whatsapp':
                error_log('REQUEST  = ' . json_encode($request->all()));
                if (!\Workflow::canEditWorkflows()) {
                    \Helper::denyAccess();
                }
                $value = '';
                $phone = '';
                $whatsapptemplate = '';
                $mailbox = Mailbox::findOrFail($request->mailbox_id);
                if (!auth()->user()->can('view', $mailbox)) {
                    \Helper::denyAccess();
                }
                $wsettings = $mailbox->meta[\WhatsApp::DRIVER] ?? [];
                $ta = \WhatsApp::getTemplates($wsettings);
                $wtemplates = [];
                foreach ($ta as $t) $wtemplates[] = json_decode($t->full, true);
                return view('whatsapp::partials/wf_whatsapp', [
                    'value' => $value,
                    'mailbox' => $mailbox,
                    'phone' => $phone,
                    'whatsapptemplate' => $whatsapptemplate,
                    'whatsapp_templates' => $wtemplates,
                    // 'and_i' => $request->param1 ?? '',
                    // 'row_i' => $request->param2 ?? '',
                ]);
                break;
        }

        abort(404);
    }

    public function getWhatsappTemplate()
    {
        $mailbox_id = $_POST['mailbox'];
        $templateId = $_POST['templateId'];

        $mailbox = Mailbox::findOrFail($mailbox_id);
        $wsettings = $mailbox->meta[\WhatsApp::DRIVER] ?? [];
        $template = [];
        $tv = \WhatsApp::getTemplate($templateId, $wsettings);
        if ($tv) {
            $tv = json_decode($tv->full, true);
            $template['id'] = $tv['id'];
            $template['name'] = $tv['name'];
            $template['params'] = [];
            if (isset($tv['components'])) {
                $template['components'] = $tv['components'];
                foreach ($tv['components'] as $c) {
                    if (isset($c['text'])) {
                        for ($x = 1; strpos($c['text'], '{{' . $x . '}}') !== false; $x++) {
                            if (!isset($template['params'][$c['type'] . '_' . $x]))
                                $template['params'][$c['type'] . '_' . $x] = '';
                            $template['params'][$c['type'] . '_' . $x] .= ' wt' . $tv['id'];
                        }
                    } else if (strtolower($c['type']) == 'header') {
                        if (in_array(strtolower($c['format']), ['image', 'document', 'video'])) {
                            $template['header']['format'] = $c['format'];
                            if (isset($c['example']['header_handle']))
                                $template['header']['files'][$c['format']] = $c['example']['header_handle'];
                            $template['header']['need_files'] = true;
                        }
                    }
                }
            }
        }

        if (!@$template['header']['need_files'] && empty(@$template['params']))
            $template['is_ready'] = true;
        return view('whatsapp::partials/whatsapp_template_form', ['template' => $template]);
    }
}
