<?php

namespace Modules\VoipeIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use App\User;

use Modules\AIAssistant\Jobs\ProcessAIResponse;
use Modules\AIAssistant\Models\Ticket;

class VoipeIntegrationController extends Controller
{
	public function webhooks(Request $request, $mailbox_id)
    {
		$data = $request->json()->all();
		$mailbox = Mailbox::find($mailbox_id);
		if (!$mailbox) 
		{
			abort(404);
        }
		error_log('VoipeIntegration webhook = '.json_encode($data));
		$settings = $mailbox->meta[\VoipeIntegration::DRIVER] ?? [];
		
		if (isset($data['event']))
		{
			if ($data['event']=='Abandoned IVR')
			{
				$data['callerid']=preg_replace('/[^01-9]+/uis','',$data['callerid']);
				$data['callerid']=preg_replace('/^[0]+/uis','',$data['callerid']);
				if (strlen($data['callerid'])>=8 && strlen($data['callerid'])<=10) $data['callerid'] = '972'.$data['callerid'];
				
				$row_id = DB::table('voipe_calls')->insertGetId(['callerid' => $data['callerid'], 'data'=>json_encode($data), 'event'=>$data['event']]);
				\VoipeIntegration::processCallsEvents($data['callerid'], $data['callerid'], $data, $mailbox, [], ['voipe_calls_row_id'=>$row_id]);
			}
			else
			{
				if (@!is_array($settings['queues'])) $settings['queues'] = explode(',',$settings['queues']);
				error_log('Queue_number '.$data['queue_number'].' ? '.json_encode($settings['queues']));
				if (in_array($data['queue_number'],$settings['queues']))
				{
					$data['callerid']=preg_replace('/[^01-9]+/uis','',$data['callerid']);
					$data['callerid']=preg_replace('/^[0]+/uis','',$data['callerid']);
					if (strlen($data['callerid'])>=8 && strlen($data['callerid'])<=10) $data['callerid'] = '972'.$data['callerid'];
					
					$row_id = DB::table('voipe_calls')->insertGetId(['callerid' => $data['callerid'], 'data'=>json_encode($data), 'event'=>$data['event']]);
					\VoipeIntegration::processCallsEvents($data['callerid'], $data['callerid'], $data, $mailbox, [], ['voipe_calls_row_id'=>$row_id]);
				}
			}
            $ticket = new Ticket;
            $ticket->message = "Hi";
            ProcessAIResponse::execute($ticket);
		}
	}

	public function settings($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        
        if (!auth()->user()->isAdmin()) {
            \Helper::denyAccess();
        }

		$settings = $mailbox->meta[\VoipeIntegration::DRIVER] ?? [];
		if (@is_array($settings['queues'])) $settings['queues'] = implode(',',$settings['queues']);

		$users = User::all();

		// error_log('VoipeIntegration check '.json_encode($settings)."\r\n");

        return view('voipeintegration::settings', [
			'users' => $users,
			'selected' => '',
            'mailbox'   => $mailbox,
            'settings'   => $settings,
			'webhookurl' => route('voipeintegration.webhook', ['mailbox_id' => $mailbox_id]),
		]);
    }

	/**
     * Settings save.
     */
    public function settingsSave(Request $request, $mailbox_id)
    {
		$mailbox = Mailbox::findOrFail($mailbox_id);
        
		
        $settings = $request->settings;
		$tsettings = $mailbox->meta[\VoipeIntegration::DRIVER] ?? [];
		if (isset($tsettings['joinconversations'])) 
		{
			$settings['joinconversations']=1;
		}
		if (isset($tsettings['reopenconversations'])) 
		{
			$settings['reopenconversations']=1;
		}

		if (is_null($settings['queues'])) $settings['queues'] = '';
		$settings['queues'] = preg_replace('/[^0-9,]/', '', $settings['queues']);
		$settings['queues'] = explode(',', $settings['queues']);
		foreach ($settings['queues'] as $k=>$v) if ($v==='') unset($settings['queues'][$k]);
		
		if (is_null($settings['landline_warning'])) $settings['landline_warning'] = '';

		foreach ($settings['templates'] as $ek=>$ev) foreach ($ev as $tk=>$tv) 
		{
			if (is_null($tv)) $settings['templates'][$ek][$tk] = '';
		} 
		// error_log('VoipeIntegration '.json_encode($settings));

        $mailbox->setMetaParam(\VoipeIntegration::DRIVER, $settings);
        $mailbox->save();

        \Session::flash('flash_success_floating', __('Settings updated'));

        return redirect()->route('mailboxes.voipeintegration.settings', ['mailbox_id' => $mailbox_id]);
    }


	public function settingscommon($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        
        if (!auth()->user()->isAdmin()) {
            \Helper::denyAccess();
        }

		$settings = $mailbox->meta[\VoipeIntegration::DRIVER] ?? [];
		$users = User::all();

        return view('voipeintegration::settingscommon', [
			'users' => $users,
			'selected' => '',
            'mailbox'   => $mailbox,
            'settings'   => $settings,
			'webhookurl' => route('voipeintegration.webhook', ['mailbox_id' => $mailbox_id]),
		]);
    }

	public function settingscommonSave(Request $request, $mailbox_id)
    {
		$mailbox = Mailbox::findOrFail($mailbox_id);
        
		$settings = $mailbox->meta[\VoipeIntegration::DRIVER] ?? [];
		if (in_array(@$request->settings['joinconversations'],[1,'1',true,'true']))
		{
			$settings['joinconversations']=1;
		}
		else
		{
			unset($settings['joinconversations']);
		}
		if (in_array(@$request->settings['reopenconversations'],[1,'1',true,'true']))
		{
			$settings['reopenconversations']=1;
		}
		else
		{
			unset($settings['reopenconversations']);
		}

        $mailbox->setMetaParam(\VoipeIntegration::DRIVER, $settings);
        $mailbox->save();

        \Session::flash('flash_success_floating', __('Settings updated'));

        return redirect()->route('mailboxes.voipeintegration.settingscommon', ['mailbox_id' => $mailbox_id]);
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('voipeintegration::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('voipeintegration::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        return view('voipeintegration::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        return view('voipeintegration::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    {
    }
}
