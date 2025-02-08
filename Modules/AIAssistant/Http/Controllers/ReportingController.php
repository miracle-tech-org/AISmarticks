<?php

namespace Modules\AIAssistant\Http\Controllers;

use DB;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\AIAssistant\Jobs\ProcessAIResponse;
use Modules\AIAssistant\Models\AIAgent;
use Modules\AIAssistant\Models\AIAgentAssignment;
use Modules\AIAssistant\Models\AIInteractionLog;
use Modules\AIAssistant\Models\Ticket;

class ReportingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $agents = AIAgent::get();

        $agentsMonthlyTokens = [];
        foreach ($agents as $agent) {
            $tokens = AIInteractionLog::select(
                DB::raw("SUM(tokens_used) as total_tokens"),
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month")
            )
                ->where('ai_agent_id', $agent->id)
                ->groupBy('month')
                ->get();

            $agent->count = AIInteractionLog::where('ai_agent_id', $agent->id)->count();

            $monthlyTokens = array_fill(0, 12, 0);
            foreach ($tokens as $item) {
                $monthIndex = (int) substr($item['month'], 5) - 1;
                $monthlyTokens[$monthIndex] = (int) $item['total_tokens'];
            }

            $agentsMonthlyTokens[] = [
                'name' => $agent->name,
                'data' => $monthlyTokens,
            ];
        }

        return view('aiassistant::reporting', compact('agentsMonthlyTokens', 'agents'));
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

        return view('aiassistant::agent-report', compact('aiAgent', 'interactionLogs', 'monthlyTokens'));
    }

    public function showTestAlert()
    {
        // $alert = new SendAlert("Hello. This is test alert");
        // $alert->handle();

        $ticket = new Ticket;
        $ticket->message = "How can I create laravel new project?";
        $job = new ProcessAIResponse($ticket);
        $job->execute();
    }

    public function initDailyUsage()
    {
        AIAgentAssignment::query()->update(['daily_usage' => 0]);
    }

    public function initMonthlyUsage()
    {
        AIAgentAssignment::query()->update(['current_usage' => 0]);
    }
}
