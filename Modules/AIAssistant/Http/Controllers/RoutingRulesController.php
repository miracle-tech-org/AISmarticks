<?php

namespace Modules\AIAssistant\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\AIAssistant\Models\RoutingRule;

class RoutingRulesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $rules = RoutingRule::with('team')
            ->orderBy('priority')
            ->get();
        $teams = \Team::getTeams();

        return view('aiassistant::routing.index', compact('rules', 'teams'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        $teams = \Team::getTeams();
        return view('aiassistant::routing.create', compact('teams'));
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $rule = new RoutingRule;
        $rule->name = $request->name;
        $rule->conditions = json_encode($request->conditions);
        $rule->priority = $request->priority;
        $rule->team_id = $request->team_id;
        $rule->required_skills = json_encode($request->required_skills);
        $rule->is_active = $request->is_active;
        $rule->save();

        return redirect('routing-rules');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id)
    {
        $teams = \Team::getTeams();
        $rule = RoutingRule::find($id);
        return view('aiassistant::routing.edit', compact('teams', 'rule'));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
        RoutingRule::find($request->id)->update([
            'name' => $request->name,
            'conditions' => json_encode($request->conditions),
            'priority' => $request->priority,
            'team_id' => $request->team_id,
            'required_skills' => json_encode($request->required_skills),
            'is_active' => $request->is_active,
        ]);
        return redirect('routing-rules');
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy($id)
    {
        RoutingRule::find($id)->delete();
        return redirect('routing-rules');
    }
}
