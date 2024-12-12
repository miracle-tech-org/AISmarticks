<?php

namespace Modules\AIAssistant\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;

use Modules\AIAssistant\Models\AIAgent;
use Modules\AIAssistant\Models\AIAgentAssignment;
use App\Mailbox;

use Modules\AIAssistant\Jobs\ProcessAIResponse;
use Modules\AIAssistant\Models\Ticket;

class AIAssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index($mailbox_id)
    {
        $aiAgents = AIAgent::where('mailbox_id', $mailbox_id)->get();
        $aiAssignment = AIAgentAssignment::where('mailbox_id', $mailbox_id)->get();

        $mailbox = Mailbox::findOrFail($mailbox_id);
        return view('aiassistant::aiassignments.index', [
            'mailbox' => $mailbox,
            'aiAgents' => $aiAgents,
            'aiAssignment' => $aiAssignment,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request, $mailbox_id)
    {
        AIAgentAssignment::where('mailbox_id', $mailbox_id)->delete();
        $assignment = new AIAgentAssignment;
        $assignment->ai_agent_id = $request->agent_id;
        $assignment->response_mode = $request->response_mode;
        $assignment->mailbox_id = $mailbox_id;

        $assignment->daily_limit = $request->daily_limit;
        $assignment->monthly_usage_limit = $request->monthly_usage_limit;
        $assignment->current_usage = $request->current_usage;
        $assignment->usage_alert_threshold = $request->usage_alert_threshold;
        $assignment->daily_usage = $request->daily_usage;
        $assignment->daily_usage_alert = $request->daily_usage_alert;
        
        $assignment->save();
        $assignment->checkUsageAlert();

       return redirect()->back()->with('success', 'Agent assigned successfully.')->with('success', 'AI Agent Assignment updated successfully!');
    }
}
