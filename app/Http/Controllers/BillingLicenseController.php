<?php

namespace App\Http\Controllers;

use App\Mailbox;
use App\LicenseLimit;
use App\User;
use App\Thread;
use App\Conversation;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\WhatsApp\Providers\WhatsAppServiceProvider;
use Modules\VoipeSmsTickets\Providers\VoipeSmsTicketsServiceProvider;
class BillingLicenseController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function view($section)
    {
//        $old = '{"mi":{"icon":"30e3428d.png"},"voipesmstickets":{"organisation":"VoIPeNew","token":"ae87d12eeec0db22ee72e6073d4b38d9","sender":"536258000","last":44055},"voipeintegration":{"pbx_server":"161.35.66.249","organisation":"test16","bucket":"-","queues":["600","601"],"events":{"incoming":"on","abandoned":"on","failover":"on"},"responder":"on","event_user":"1","landline_warning":"Please notice this is a landline phone number which is not receiving sms messages. Please contact the customer via a phone call.","templates":{"incoming":{"subject":"%event% %date_time%","body":"%event% %date_time%\r\nQueue: %queue_number% (%queue_name%)\r\nExtensions: %extensions%\r\nCallerid: %callerid%"},"abandoned":{"subject":"%event% %date_time%","body":"%event% %date_time%\r\nQueue: %queue_number% (%queue_name%)\r\nWait time: %wait_time%\r\nDID: %did%\r\nCallerid: %callerid%"},"failover":{"subject":"%event% %date_time%","body":"%event% %date_time%\r\nQueue: %queue_number% (%queue_name%)\r\nWait time: %wait_time%\r\nCallerid: %callerid%\r\nDST: %failover_destination%"},"responder":{"subject":"%event% %date_time%","body":"%event% %date_time%\r\nCallerid: %callerid%\r\nSenderid: %senderid%\r\nTemplate: \"%template%\""}}}}';
//        $new = '{"mi":{"icon":"c57ebd12.png"},"voipesmstickets":{"organisation":"mercazgalay","token":"31fe89c505e27072de33866f85955dd7","sender":"0536257166","last":44341},"imapmove":{"action":1,"folder":""},"voipeintegration":{"pbx_server":"161.35.66.249","organisation":"test16","bucket":"test16","queues":["600"],"events":{"abandoned":"on"},"responder":"on","event_user":"2","landline_warning":"\u05e9\u05d9\u05de\u05d5 \u05dc\u05d1, \u05d4\u05dc\u05e7\u05d5\u05d7 \u05d4\u05ea\u05e7\u05e9\u05e8 \u05de\u05de\u05e1\u05e4\u05e8 \u05e7\u05d5\u05d5\u05d9. \u05d9\u05e9 \u05dc\u05d9\u05e6\u05d5\u05e8 \u05e7\u05e9\u05e8 \u05d8\u05dc\u05e4\u05d5\u05e0\u05d9 \u05e2\u05dd \u05d4\u05dc\u05e7\u05d5\u05d7","templates":{"incoming":{"subject":"","body":""},"abandoned":{"subject":"\u05e9\u05d9\u05d7\u05d4 \u05e0\u05e0\u05d8\u05e9\u05ea \u05d7\u05d3\u05e9\u05d4","body":"\u05d9\u05e9 \u05e9\u05d9\u05d7\u05d4 \u05e0\u05e0\u05d8\u05e9\u05ea \u05d7\u05d3\u05e9\u05d4 \u05d1\u05ea\u05d5\u05e8 %queue_name% %queue_number% \u05d1\u05ea\u05d0\u05e8\u05d9\u05da %date_time% \u05de\u05d4\u05de\u05e1\u05e4\u05e8 %callerid%. \r\n\u05d4\u05dc\u05e7\u05d5\u05d7 \u05d7\u05d9\u05d9\u05d2 \u05dc\u05de\u05e1\u05e4\u05e8 %did% \u05d5\u05d4\u05de\u05ea\u05d9\u05df %wait_time%."},"failover":{"subject":"","body":""},"responder":{"subject":"\u05d1\u05e7\u05e9\u05ea \u05e8\u05d9\u05e1\u05e4\u05d5\u05e0\u05d3\u05e8 \u05d7\u05d3\u05e9\u05d4","body":"\u05d1\u05e7\u05e9\u05ea \u05e8\u05d9\u05e1\u05e4\u05d5\u05e0\u05d3\u05e8 \u05d7\u05d3\u05e9\u05d4 \u05d4\u05ea\u05e7\u05d1\u05dc\u05d4 \u05d1%date_time% \u05de\u05d0\u05ea %callerid%.\r\n\u05ea\u05d1\u05e0\u05d9\u05ea \u05d4\u05e8\u05d9\u05e1\u05e4\u05d5\u05e0\u05d3\u05e8- %template%\r\n\u05de\u05d6\u05d4\u05d4 \u05e9\u05d5\u05dc\u05d7- %senderid%\r\n\u05e0\u05e9\u05dc\u05d7 \u05d1\u05d0\u05d9\u05e8\u05d5\u05e2- %event%"}},"joinconversations":1},"whatsapp":{"enabled":1,"initiate_enabled":1,"system":"1","instance":"HEI13801190","token":"FITjM3W3wOwIpw5C65uwhc53Ek7Gbh7w","twilio_sid":null,"twilio_token":null,"twilio_phone_number":null},"kb":{"site_name":"VIKI","domain":null,"footer":"\u05db\u05dc \u05d4\u05d6\u05db\u05d5\u05d9\u05d5\u05ea \u05e9\u05de\u05d5\u05e8\u05d5\u05ea","menu":"[\u05e7\u05d9\u05e9\u05d5\u05e8 \u05dc\u05e4\u05d0\u05e0\u05dc](https:\/\/CPanel.voipe.cc\/)[\u05e7\u05d9\u05e9\u05d5\u05e8 \u05dc\u05d0\u05ea\u05e8](https:\/\/voipe.co.il\/)","locales":["he","en","ru"],"visibility":"1"},"sr":{"add":"1","saving_mode":"2"},"eup":{"text_submit":"\u05e6\u05d5\u05e8 \u05e7\u05e9\u05e8","subject":"1","consent":"1","privacy":"<div>\u05d0\u05e0\u05d9 \u05de\u05d0\u05e9\u05e8 \u05d0\u05ea....<\/div>","numbers":"1","footer":"\u00a9 {%year%} {%mailbox.name%}"}}';
//
//        Mailbox::updateOrCreate(
//            ['id' => 1], // Условие: поиск пользователя с указанным email
//            ['meta' => json_decode($new)] // Данные для обновления или создания новой записи
//        );exit;

		$agents = DB::select("SELECT * FROM ai_agents");

        $section_name = 'License and subscription';
        if ($section == 'license')
            $section_name = 'License';

        switch ($section) {
            case 'license': case 'license_quota':
                $section_name = 'BLC';
                $data = LicenseLimit::getLimits();
				$data['mailboxes_list'] = Mailbox::get();
                break;
            default:
                $section_name = 'License and subscription';
                $limits = LicenseLimit::getLimits();
                $result = DB::select('SELECT table_schema AS `database`, SUM(data_length + index_length) / 1024 / 1024 AS `size_in_mb` FROM information_schema.tables WHERE table_schema = ? GROUP BY table_schema;', ['freescout']);
                $folderSize = $this->getFolderSize('/var/www/html');
                $mailboxes = Mailbox::select(['name', 'meta'])
                    ->where(DB::raw("json_extract(meta, '$.whatsapp.enabled')"), 1)
                    ->get()
                    ->toArray();

                $channelsID = [];
                foreach ($mailboxes as $mailbox) {
                    $config = $mailbox['meta']['whatsapp'] ?? [];
                    if (isset($config['instance'])) {
                        $channelsID[$mailbox['name']] = $config['instance'];
                    }
                }

				$whatsapp_in = DB::select('SELECT `cnt` FROM `billing_statistics` WHERE `type` = ? AND `month`=?', ['whatsapp_in',date('Y-m')]);
				if (isset($whatsapp_in[0]))
				{
					$whatsapp_in=$whatsapp_in[0]->cnt;
				}
				else
				{
					$whatsapp_in=0;
				}
				$whatsapp_out = DB::select('SELECT `cnt` FROM `billing_statistics` WHERE `type` = ? AND `month`=?', ['whatsapp_out',date('Y-m')]);
				if (isset($whatsapp_out[0]))
				{
					$whatsapp_out=$whatsapp_out[0]->cnt;
				}
				else
				{
					$whatsapp_out=0;
				}
				$whatsapp_marketing = DB::select('SELECT `cnt` FROM `billing_statistics` WHERE `type` = ? AND `month`=?', ['wtcatmarketing',date('Y-m')]);
				if (isset($whatsapp_marketing[0]))
				{
					$whatsapp_marketing=$whatsapp_marketing[0]->cnt;
				}
				else
				{
					$whatsapp_marketing=0;
				}
				$whatsapp_utility = DB::select('SELECT `cnt` FROM `billing_statistics` WHERE `type` = ? AND `month`=?', ['wtcatutility',date('Y-m')]);
				if (isset($whatsapp_utility[0]))
				{
					$whatsapp_utility=$whatsapp_utility[0]->cnt;
				}
				else
				{
					$whatsapp_utility=0;
				}
				$whatsapp_authentication = DB::select('SELECT `cnt` FROM `billing_statistics` WHERE `type` = ? AND `month`=?', ['wtcatauthentication',date('Y-m')]);
				if (isset($whatsapp_authentication[0]))
				{
					$whatsapp_authentication=$whatsapp_authentication[0]->cnt;
				}
				else
				{
					$whatsapp_authentication=0;
				}
				// $total_whatsapp = $this->getCountOutgoingMessages(WhatsAppServiceProvider::CHANNEL);
				// $total_whatsapp_us_marketing = DB::table('threads')
				// 	->join('conversations', 'threads.conversation_id', '=', 'conversations.id')
				// 	->whereIn('conversations.channel', [WhatsAppServiceProvider::CHANNEL])
				// 	->whereIn('threads.type', [Thread::TYPE_MESSAGE])
				// 	->where('threads.meta', 'like', '%wtcatmarketing%')
				// 	->whereBetween('threads.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
				// 	->count();
				// $total_whatsapp_us_utility = DB::table('threads')
				// 	->join('conversations', 'threads.conversation_id', '=', 'conversations.id')
				// 	->whereIn('conversations.channel', [WhatsAppServiceProvider::CHANNEL])
				// 	->whereIn('threads.type', [Thread::TYPE_MESSAGE])
				// 	->where('threads.meta', 'like', '%wtcatutility%')
				// 	->whereBetween('threads.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
				// 	->count();
				// $total_whatsapp_us_authentication = DB::table('threads')
				// 	->join('conversations', 'threads.conversation_id', '=', 'conversations.id')
				// 	->whereIn('conversations.channel', [WhatsAppServiceProvider::CHANNEL])
				// 	->whereIn('threads.type', [Thread::TYPE_MESSAGE])
				// 	->where('threads.meta', 'like', '%wtcatauthentication%')
				// 	->whereBetween('threads.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
				// 	->count();
				// $total_whatsapp_customer = $total_whatsapp - $total_whatsapp_us_marketing - $total_whatsapp_us_utility - $total_whatsapp_us_authentication;

                $countWorkflows = DB::table('workflows')
					->join('mailboxes', 'workflows.mailbox_id', '=', 'mailboxes.id')
            		->where('workflows.active',1)
					->whereNotNull('mailboxes.id');
				error_log($countWorkflows->toSql());
				$countWorkflows=$countWorkflows->count();
                $countUsers = User::nonDeleted()->where('status',1)->where(['role' => User::ROLE_USER])->get();
                $countUsers = User::sortUsers($countUsers)->count();
                $countAdmins = User::nonDeleted()->where('status',1)->where(['role' => User::ROLE_ADMIN])->get();
                $countAdmins = User::sortUsers($countAdmins)->count();

				$sms_in = DB::select('SELECT `cnt` FROM `billing_statistics` WHERE `type` = ? AND `month`=?', ['sms_in',date('Y-m')]);
				if (isset($sms_in[0]))
				{
					$sms_in=$sms_in[0]->cnt;
				}
				else
				{
					$sms_in=0;
				}
				$sms_out = DB::select('SELECT `cnt` FROM `billing_statistics` WHERE `type` = ? AND `month`=?', ['sms_out',date('Y-m')]);
				if (isset($sms_out[0]))
				{
					$sms_out=$sms_out[0]->cnt;
				}
				else
				{
					$sms_out=0;
				}

				$sms_in_prev = DB::select('SELECT `cnt` FROM `billing_statistics` WHERE `type` = ? AND `month`=?', ['sms_in',date('Y-m',(int)microtime(true)-date('d')*24*3600)]);
				if (isset($sms_in_prev[0]))
				{
					$sms_in_prev=$sms_in_prev[0]->cnt;
				}
				else
				{
					$sms_in_prev=0;
				}
				$sms_out_prev = DB::select('SELECT `cnt` FROM `billing_statistics` WHERE `type` = ? AND `month`=?', ['sms_out',date('Y-m',(int)microtime(true)-date('d')*24*3600)]);
				if (isset($sms_out_prev[0]))
				{
					$sms_out_prev=$sms_out_prev[0]->cnt;
				}
				else
				{
					$sms_out_prev=0;
				}

				$prev_monthes=[];
				$qa = DB::select('SELECT * FROM `billing_statistics` WHERE `month`>? AND `month`!=? ORDER BY `month`', [date('Y-m',(int)microtime(true)-367*24*3600),date('Y-m')]);
				foreach ($qa as $q)
				{
					if (!isset($prev_monthes[$q->month]))
					{
						$prev_monthes[$q->month]=[
							'whatsapp_in'=>0,
							'whatsapp_out'=>0,
							'wtcatmarketing'=>0,
							'wtcatutility'=>0,
							'wtcatauthentication'=>0,
							'sms_in'=>0,
							'sms_out'=>0,
							'count_workflows'=>0,
						];
					}
					$prev_monthes[$q->month][$q->type]=$q->cnt;
				}
				
				$data = [
                    'email' => $limits['email'],
                    'max_mailboxes' => $limits['mailbox'],
                    'max_workflows' => $limits['workflow'],
                    'max_admin' => $limits['max_admin'],
                    'max_user'  => $limits['max_user'],
                    'mailboxes' => Mailbox::count(),
                    'users' => ['admin' => $countAdmins, 'user' => $countUsers],
                    'db_size' => $this->formatSizeUnits($result[0]->size_in_mb),
                    'folder_size' => $this->formatSize($folderSize),
                    'sms_count' => ($sms_in+$sms_out),
                    'sms_incoming_count' => $sms_in,
                    'sms_outgoing_count' => $sms_out,
                    'sms_count_prev' => ($sms_in_prev+$sms_out_prev),
                    'sms_incoming_count_prev' => $sms_in_prev,
                    'sms_outgoing_count_prev' => $sms_out_prev,
                    'whatsapp_count' => $whatsapp_in+$whatsapp_out,
                    'whatsapp_incoming_count' => $whatsapp_in,
                    'whatsapp_outgoing_count' => $whatsapp_out,
                    'channels_id' => $channelsID,
                    'whatsapp_outgoing_authentication' => $whatsapp_authentication,
                    'whatsapp_outgoing_utility' => $whatsapp_utility,
                    'whatsapp_outgoing_marketing' => $whatsapp_marketing,
					'number_of_set_workflows' => $countWorkflows,
					'prev_monthes' => $prev_monthes
                ];
        }
        return view('billing-license/view', ['section' => $section, 'section_name' => $section_name, 'data' => $data, 'agents' => $agents]);
    }

    public function viewSave($section, Request $request)
    {
//        $this->appDebug($section);
        switch ($section) {
            case 'license':
                $email = !empty($request->email)?$request->email:'';
                $mailbox     = (!empty((int)$request->mailbox)   || (int)$request->mailbox   != 0) ? (int)$request->mailbox   : null;
                $maxAdmins = (!empty((int)$request->max_admin) || (int)$request->max_admin != 0) ? (int)$request->max_admin : null;
                $maxUsers  = (!empty((int)$request->max_user)  || (int)$request->max_user  != 0) ? (int)$request->max_user  : null;
                $workflow  = (!empty((int)$request->workflow)  || (int)$request->workflow  != 0) ? (int)$request->workflow  : null;

                $validator = Validator::make($request->all(), [
                    'mailbox'   => 'nullable|integer|min:0|max:255',
                    'max_admin' => 'nullable|integer|min:0|max:255',
                    'max_user'  => 'nullable|integer|min:0|max:255',
                    'workflow'  => 'nullable|integer|min:0|max:255'
                ]);

                if ($validator->fails()) {
                    return redirect()->route('billing_license', ['section' => $section])
                        ->withErrors($validator)
                        ->withInput();
                }

                $countWorkflows = DB::table('workflows')
                    ->join('mailboxes', 'workflows.mailbox_id', '=', 'mailboxes.id')
                    ->where('workflows.active',1)
                    ->whereNotNull('mailboxes.id')->count();

                if ($workflow<$countWorkflows)
                {
                    return redirect()->route('billing_license', ['section' => $section])
                        ->withErrors(['workflow'=>'Please first disable workflows before editing the number of allowed workflows'])
                        ->withInput();
                }

                $countUsers = User::nonDeleted()->where('status',1)->where(['role' => User::ROLE_USER])->get();
                $countUsers = User::sortUsers($countUsers)->count();

                if ($maxUsers<$countUsers)
                {
                    return redirect()->route('billing_license', ['section' => $section])
                        ->withErrors(['max_user'=>'Please first disable users before editing the number of allowed users'])
                        ->withInput();
                }

                $countAdmins = User::nonDeleted()->where('status',1)->where(['role' => User::ROLE_ADMIN])->get();
                $countAdmins = User::sortUsers($countAdmins)->count();

                if ($maxAdmins<$countAdmins)
                {
                    return redirect()->route('billing_license', ['section' => $section])
                        ->withErrors(['max_admin'=>'Please first disable admins before editing the number of allowed admins'])
                        ->withInput();
                }

                if ($mailbox<Mailbox::count())
                {
                    return redirect()->route('billing_license', ['section' => $section])
                        ->withErrors(['mailbox'=>'Please first delete mailboxes before editing the number of mailboxes'])
                        ->withInput();
                }

                LicenseLimit::updateOrCreate(['id' => 1], ['email' => $email, 'mailbox' => $mailbox, 'max_admin' => $maxAdmins, 'max_user' => $maxUsers, 'workflow' => $workflow]);

                $updateText = 'License updated';

                break;
            case 'license_quota':
                $smsOutQuota  = (int)$request->sms_out_quota > 0 ? (int)$request->sms_out_quota  : null;
                $whatsappOutQuota  = (int)$request->whatsapp_out_quota > 0 ? (int)$request->whatsapp_out_quota  : null;
                $dbSizeQuota  = (int)$request->db_size_quota > 0 ? (int)$request->db_size_quota  : null;
                $blockOverQuotaWhatsapp  = (int)$request->block_over_quota_whatsapp ? 1 : 0;
                $blockOverQuotaSMS  = (int)$request->block_over_quota_sms ? 1 : 0;
//$this->appDebug($blockOverQuotaWhatsapp, true, true);

                $validator = Validator::make($request->all(), [
                    'sms_out_quota'             => 'nullable|integer|min:0|max:255',
                    'whatsapp_out_quota'        => 'nullable|integer|min:0|max:255',
                    'db_size_quota'             => 'nullable|integer|min:0|max:255',
                    'block_over_quota_whatsapp' => 'nullable|boolean',
                    'block_over_quota_sms'      => 'nullable|boolean',
                ]);

                if ($validator->fails()) {
                    return redirect()->route('billing_license', ['section' => $section])
                        ->withErrors($validator)
                        ->withInput();
                }
				LicenseLimit::updateOrCreate(['id' => 1],
                    [
                        'sms_out_quota' => $smsOutQuota,
                        'whatsapp_out_quota' => $whatsappOutQuota,
                        'db_size_quota' => $dbSizeQuota,
                        'block_over_quota_whatsapp' => $blockOverQuotaWhatsapp,
                        'block_over_quota_sms' => $blockOverQuotaSMS
                    ]
                );
                $updateText = 'License quota updated';
                break;
        }

        $request->session()->flash('flash_success_floating', __($updateText));

        return redirect()->route('billing_license', ['section' => $section]);

    }

    public function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = ['sum' => number_format($bytes / 1073741824, 2), 'type' => 'gb'];
        } elseif ($bytes >= 1048576) {
            $bytes = ['sum' => number_format($bytes / 1048576, 2), 'type' => 'mb'];
        } elseif ($bytes >= 1024) {
            $bytes = ['sum' => number_format($bytes / 1024, 2), 'type' => 'kb'];
        } elseif ($bytes > 1) {
            $bytes = ['sum' => $bytes, 'type' => 'bytes'];
        } elseif ($bytes == 1) {
            $bytes = ['sum' => $bytes, 'type' => 'byte'];
        } else {
            $bytes = ['sum' => 0, 'type' => 'bytes'];
        }

        return $bytes;
    }

    public function getFolderSize($dir)
    {
        $size = 0;

        foreach (scandir($dir) as $file) {
            // Игнорируем ссылки на текущую и родительскую директории
            if ($file == '.' || $file == '..') continue;

            $path = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                // Рекурсивно считаем размер вложенных папок
                $size += self::getFolderSize($path);
            } else {
                if (empty($path))
                    continue;
                if (file_exists($path)) {
                    // Получаем размер файла и добавляем его к общему размеру
                    $size += filesize($path);
                }
            }
        }

        return $size;
    }
    public function formatSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size >= 1024 && $i < 4; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    private function getCountMessagesByChannel($channel)
    {
        return DB::table('threads')
            ->join('conversations', 'threads.conversation_id', '=', 'conversations.id')
            ->whereIn('conversations.channel', [$channel])
            ->whereIn('threads.type', [
                Thread::TYPE_CUSTOMER,
                Thread::TYPE_MESSAGE,
                Thread::TYPE_CHAT
            ])
            ->whereBetween('threads.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->count();
    }

    private function getCountIncomingMessages($channel)
    {
        return DB::table('threads')
            ->join('conversations', 'threads.conversation_id', '=', 'conversations.id')
            ->whereIn('conversations.channel', [$channel])
            ->whereIn('threads.type', [Thread::TYPE_CUSTOMER])
            ->whereBetween('threads.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->count();
    }

    private function getCountOutgoingMessages($channel)
    {
        return DB::table('threads')
            ->join('conversations', 'threads.conversation_id', '=', 'conversations.id')
            ->whereIn('conversations.channel', [$channel])
            ->whereIn('threads.type', [Thread::TYPE_MESSAGE])
            ->whereBetween('threads.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->count();
    }
}