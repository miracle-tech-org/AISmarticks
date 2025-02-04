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
    public function index(Request $request)
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
    public function create(Request $request)
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

        return redirect()->route('routing.index');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id, Request $request)
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
        return redirect()->route('routing.index');
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy($id)
    {
        RoutingRule::find($id)->delete();
        return redirect()->route('routing.index');
    }
}
