<?php

namespace Modules\AIAssistant\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Modules\AIAssistant\Http\Requests\AIAgentRequest;
use Modules\AIAssistant\Models\AIAgent;
use Modules\AIAssistant\Models\AIAgentAssignment;

class AIAgentController extends Controller
{

    public function __construct()
    {
        // $this->middleware('can:manage_ai_agents');
    }
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $aiAgents = AIAgent::get();
        return view('aiassistant::aiagents.index', [
            'aiAgents' => $aiAgents,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('aiassistant::aiagents.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(AIAgentRequest $request)
    {
        $validatedInputs = $request->validated();
        $data = $request->all();

        $aiAgent = AIAgent::create($data);

        // Create default assignment with rate limiting
        AIAgentAssignment::create([
            'ai_agent_id' => $aiAgent->id,
            'monthly_usage_limit' => 10000, // Default monthly usage limit
            'daily_limit' => 350, // Default daily usage limit
            'current_usage' => 0, // Default usage alert threshold
            'daily_usage' => 0, // Default usage alert threshold
            'usage_alert_threshold' => 80, // Default usage alert threshold
            'daily_usage_alert' => 80, // Default usage alert threshold
        ]);

        return redirect()->route('aiagents.index')->with('success', 'AI Agent created successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id)
    {
        $aiAgent = AIAgent::find($id);

        $aiAgent->openai_api_key = Crypt::encryptString($aiAgent->openai_api_key);

        return view('aiassistant::aiagents.edit', [
            'aiAgent' => $aiAgent,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(AIAgentRequest $request, $id)
    {
        $validatedInputs = $request->validated();

        $aiAgent = AIAgent::find($id);
        $data = $request->all();
        if (substr($data['openai_api_key'], 0, 3) !== 'sk-') {
            unset($data['openai_api_key']);
        }

        $aiAgent->update($data);

        return redirect()->route('aiagents.index')->with('success', 'AI Agent updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy($id)
    {
        $aiAgent = AIAgent::find($id);
        $aiAgent->delete();
        return redirect()->route('aiagents.index')->with('success', 'AI Agent deleted successfully!');
    }
}
