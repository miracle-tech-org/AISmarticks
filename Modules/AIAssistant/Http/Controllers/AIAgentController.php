<?php

namespace Modules\AIAssistant\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Modules\AIAssistant\Http\Requests\AIAgentRequest;
use Modules\AIAssistant\Models\AIAgent;
use DB;
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
        return view('aiassistant::aiagents.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(AIAgentRequest $request)
    {
        $data = $request->all();
        $parsedUrl = parse_url(route('aiagents.session'));
        $host = $parsedUrl['host'];
        $subdomain = explode('.', $host)[0];
        $noSpaces = str_replace(' ', '', $request->name);

        $user = DB::table('users')->insert([
            'first_name' => 'AIAgent',
            'last_name' => $request->name,
            'email' => 'aiagent.' . $subdomain . '.' . $noSpaces . '@smarticks.co',
            'password' => bcrypt('123$%^qweRTY'),
            'role' => '1',
            'timezone' => 'UTC',
            'type' => '1',
            'status' => '1',
            'invite_state' => '1',
            'time_format' => '2',
            'enable_kb_shortcuts' => '1',
            'locked' => '0',
            'open_on_this_page' => '0',
            'available' => '1',
            'bot' => '1',
            'photo_url' => 'robot.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data['user_id'] = User::latest()->first()->id;

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
        return view('aiassistant::aiagents.edit', compact('aiAgent'));
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
