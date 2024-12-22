<?php

namespace Modules\AIAssistant\Http\Controllers;

use App\Jobs\SendAlert;
use App\Mailbox;
use DB;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\AIAssistant\Models\AIAgent;
use Modules\AIAssistant\Models\AIInteractionLog;
use Modules\AIAssistant\Models\AIAgentAssignment;

class ReportingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $aiAgents = AIAgent::all();
        $interactionLogs = AIInteractionLog::all();
        $tokens = AIInteractionLog::select(DB::raw("SUM(tokens_used) as total_tokens"), DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"))
            ->groupBy('month')
            ->get();

        $monthlyTokens = array_fill(0, 12, 0);
        foreach ($tokens as $item) {
            $monthIndex = (int) substr($item['month'], 5) - 1;
            $monthlyTokens[$monthIndex] = (int) $item['total_tokens'];
        }

        $mailboxes = Mailbox::get();

        $agentResult = DB::select("
            SELECT b.id AS id,
                c.name AS mailbox_name,
                c.id AS mailbox_id,
                b.name AS ai_agent_name,
                (SELECT SUM(tokens_used) FROM ai_interaction_logs AS d WHERE d.ai_agent_id = a.ai_agent_id) AS tokens_used,
                (SELECT COUNT(*) FROM ai_interaction_logs AS d WHERE d.ai_agent_id = a.ai_agent_id) AS conversations
            FROM ai_agent_assignments AS a
            RIGHT JOIN ai_agents AS b ON a.ai_agent_id = b.id
            RIGHT JOIN mailboxes AS c ON a.mailbox_id = c.id
		");
        $agentsByMailbox = collect($agentResult)->groupBy('mailbox_name');

        return view('aiassistant::reporting', compact('aiAgents', 'monthlyTokens', 'mailboxes', 'agentsByMailbox'));
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show($id)
    {
        $aiAgent = AIAgent::find($id);
        $interactionLogs = AIInteractionLog::where('ai_agent_id', $id)->get();

        $tokens = AIInteractionLog::select(DB::raw("SUM(tokens_used) as total_tokens"), DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"))
            ->where('ai_agent_id', $id)
            ->groupBy('month')
            ->get();

        $monthlyTokens = array_fill(0, 12, 0);
        foreach ($tokens as $item) {
            $monthIndex = (int) substr($item['month'], 5) - 1;
            $monthlyTokens[$monthIndex] = (int) $item['total_tokens'];
        }

        $result = DB::select("SELECT *FROM ai_interaction_logs WHERE ai_agent_id = $id GROUP BY conversation_id;");
        $conversationCount = count($result);

        return view('aiassistant::agent-report', compact('aiAgent', 'interactionLogs', 'monthlyTokens', 'conversationCount'));
    }

    public function showTestAlert()
    {
        $alert = new SendAlert("Hello. This is test alert");
        $alert->handle();
    }

    public function initDailyUsage()
    {
        AIAgentAssignment::query()->update(['daily_usage' => 0]);
    }

    public function initMonthlyUsage()
    {
        AIAgentAssignment::query()->update(['current_usage' => 0]);
    }

    public function showMailbox($mailbox_id)
    {
        $aiAgents = AIAgent::where('mailbox_id', $mailbox_id)->get();
        $interactionLogs = AIInteractionLog::all();
        $tokens = AIInteractionLog::select(DB::raw("SUM(tokens_used) as total_tokens"), DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"))
            ->groupBy('month')
            ->get();

        $monthlyTokens = array_fill(0, 12, 0);
        foreach ($tokens as $item) {
            $monthIndex = (int) substr($item['month'], 5) - 1;
            $monthlyTokens[$monthIndex] = (int) $item['total_tokens'];
        }

        $mailboxes = Mailbox::get();

        return view('aiassistant::reportmailbox', compact('aiAgents', 'monthlyTokens', 'mailboxes')); 
    }
}
