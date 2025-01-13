<?php

namespace Modules\AIMiddleware\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\AIMiddleware\Services\{AIService, AlertService, LoggerService};
use Modules\MyImprovements\Models\AIAgent;
use App\Option;

class AIAgentController extends Controller
{
    protected AlertService $alertService;
    protected AIService $aiService;
    protected LoggerService $logger;

    /**
     * Constructor to inject dependencies
     */
    public function __construct(
        AlertService $alertService, 
        AIService $aiService,
        LoggerService $logger
    ) {
        $this->middleware(['auth', 'admin']);
        $this->alertService = $alertService;
        $this->aiService = $aiService;
        $this->logger = $logger;
    }

    /**
     * Display list of AI agents
     */
    public function index()
    {
        try {
            $agents = AIAgent::with('interactions')
                ->withCount('interactions')
                ->paginate(Option::get('my_improvements.items_per_page', 10));

            $this->logger->info('AI agents list viewed', [
                'count' => $agents->count(),
                'page' => request()->get('page', 1)
            ]);

            return view('my_improvements::agents.index', [
                'agents' => $agents,
                'stats' => $this->getAgentStats()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->exception($e, ['action' => 'view_agents']);
            $this->alertService->error(__('Unable to load AI agents'));
            return redirect()->back();
        }
    }

    /**
     * Store a new AI agent
     */
    public function store(AIAgentRequest $request)
    {
        try {
            $agent = AIAgent::create($request->validated());

            // Set as default if first agent
            if (AIAgent::count() === 1) {
                Option::set('my_improvements.default_agent_id', $agent->id);
            }

            $this->logger->userAction('create_ai_agent', [
                'agent_id' => $agent->id,
                'model' => $agent->model
            ]);

            $this->alertService->success(__('AI Agent created successfully'));
            return redirect()->route('my_improvements.agents.index');

        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'action' => 'create_agent',
                'input' => $request->safe()->except(['api_key'])
            ]);
            $this->alertService->error(__('Failed to create AI agent'));
            return redirect()->back()->withInput();
        }
    }

    /**
     * Test AI agent connection
     */
    public function test(AIAgent $agent)
    {
        try {
            $startTime = microtime(true);
            $response = $this->aiService->testConnection($agent);
            $duration = (microtime(true) - $startTime) * 1000;

            $this->logger->apiInteraction('test_connection', [
                'agent_id' => $agent->id,
                'duration' => $duration,
                'success' => $response['success']
            ]);

            // Check for slow response
            $this->alertService->slowResponse($duration);

            if ($response['success']) {
                $this->alertService->success(__(
                    'Connection successful (:time ms)',
                    ['time' => round($duration)]
                ));
            } else {
                $this->alertService->error($response['error']);
            }

            return redirect()->back();

        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'action' => 'test_connection',
                'agent_id' => $agent->id
            ]);
            
            $this->alertService->error(__('Connection test failed'));
            return redirect()->back();
        }
    }

    /**
     * Update an AI agent
     */
    public function update(AIAgentRequest $request, AIAgent $agent)
    {
        try {
            $oldValues = $agent->getAttributes();
            $agent->update($request->validated());
            
            $this->logger->userAction('update_ai_agent', [
                'agent_id' => $agent->id,
                'changes' => array_diff_assoc(
                    $agent->getAttributes(),
                    array_merge($oldValues, ['api_key' => '***'])
                )
            ]);

            $this->alertService->success(__('AI Agent updated successfully'));
            return redirect()->route('my_improvements.agents.index');

        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'action' => 'update_agent',
                'agent_id' => $agent->id,
                'input' => $request->safe()->except(['api_key'])
            ]);
            $this->alertService->error(__('Failed to update AI agent'));
            return redirect()->back()->withInput();
        }
    }

    /**
     * Delete an AI agent
     */
    public function destroy(AIAgent $agent)
    {
        try {
            $agentId = $agent->id;
            $agent->delete();
            
            $this->logger->userAction('delete_ai_agent', [
                'agent_id' => $agentId
            ]);

            $this->alertService->success(__('AI Agent deleted successfully'));
            return redirect()->route('my_improvements.agents.index');

        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'action' => 'delete_agent',
                'agent_id' => $agent->id
            ]);
            $this->alertService->error(__('Failed to delete AI agent'));
            return redirect()->back();
        }
    }
}

