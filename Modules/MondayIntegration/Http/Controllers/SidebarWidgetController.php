<?php

namespace Modules\MondayIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Mailbox;
use Modules\MondayIntegration\Models\MondaySettings;

class SidebarWidgetController extends Controller
{
    public function index($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $settings = MondaySettings::all()->pluck('value', 'key')->toArray();
        return view('mondayintegration::settings.sidebar_widget', compact('mailbox', 'settings'));
    }
}
