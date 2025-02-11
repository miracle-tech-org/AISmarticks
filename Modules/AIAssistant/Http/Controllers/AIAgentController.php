<?php

namespace Modules\AIAssistant\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Modules\AIAssistant\Http\Requests\AIAgentRequest;
use Modules\AIAssistant\Models\AIAgent;
use App\User;

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
        $users = User::nonDeleted()->where('role', 1)->get();
        $users = User::sortUsers($users);

        return view('aiassistant::aiagents.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(AIAgentRequest $request)
    {
        $data = $request->all();
        AIAgent::create($data);
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

        $users = User::nonDeleted()->where('role', 1)->get();
        $users = User::sortUsers($users);

        return view('aiassistant::aiagents.edit', compact('aiAgent', 'users'));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(AIAgentRequest $request, $id)
    {
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
        $aiAgent = AIAgent::find($id)->delete();
        return redirect()->route('aiagents.index')->with('success', 'AI Agent deleted successfully!');
    }
}
