<?php

namespace Modules\AIAssistant\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;

use Modules\AIAssistant\Http\Requests\AIAgentRequest;
use Modules\AIAssistant\Models\AIAgent;
use Modules\AIAssistant\Models\AIAgentAssignment;
use App\Mailbox;

class AIAgentController extends Controller
{

    public function __construct() {
        // $this->middleware('can:manage_ai_agents');
    }
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index($mailbox_id)
    {
        $aiAgents = AIAgent::all();
        $mailbox = Mailbox::findOrFail($mailbox_id);
        return view('aiassistant::aiagents.index', [
            'mailbox' => $mailbox,
            'aiAgents' => $aiAgents
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        return view('aiassistant::aiagents.create', [
            'mailbox' => $mailbox
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(AIAgentRequest $request, $mailbox_id)
    {
        $validatedInputs = $request->validated();
        $aiAgent = AIAgent::create($request->all());

        // Create default assignment with rate limiting
        AIAgentAssignment::create([
            'ai_agent_id' => $aiAgent->id,
            'monthly_usage_limit' => 10000, // Default monthly usage limit
            'usage_alert_threshold' => 80, // Default usage alert threshold
        ]);

        return redirect()->route('aiagents.index', ['mailbox_id' => $mailbox_id])->with('success', 'AI Agent created successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($mailbox_id)
    {
        $id = request()->query('id');
        $aiAgent = AIAgent::find($id);

        $aiAgent->openai_api_key = Crypt::encryptString($aiAgent->openai_api_key);

        $mailbox = Mailbox::findOrFail($mailbox_id);
        return view('aiassistant::aiagents.edit', [
            'mailbox' => $mailbox,
            'aiAgent' => $aiAgent
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(AIAgentRequest $request, $mailbox_id, $id)
    {
        $validatedInputs = $request->validated();

        $aiAgent = AIAgent::find($id);
        $aiAgent->update($request->all());

        return redirect()->route('aiagents.index', ['mailbox_id' => $mailbox_id])->with('success', 'AI Agent updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy($mailbox_id, $id)
    {
        $aiAgent = AIAgent::find($id);
        $aiAgent->delete();
        return redirect()->route('aiagents.index', ['mailbox_id' => $mailbox_id])->with('success', 'AI Agent deleted successfully!');
    }
}
