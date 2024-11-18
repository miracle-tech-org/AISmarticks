<?php

namespace Modules\AIAssistant\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\AIAssistant\Models\AIAgent;
use Modules\AIAssistant\Models\AIInteractionLog;
use DB;

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
            $monthIndex = (int)substr($item['month'], 5) - 1;
            $monthlyTokens[$monthIndex] = (int)$item['total_tokens'];
        }

        return view('aiassistant::reporting', compact('aiAgents', 'monthlyTokens'));
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
            $monthIndex = (int)substr($item['month'], 5) - 1;
            $monthlyTokens[$monthIndex] = (int)$item['total_tokens'];
        }

        return view('aiassistant::agent-report', compact('aiAgent', 'interactionLogs', 'monthlyTokens'));
    }
}
