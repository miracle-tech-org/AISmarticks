<?php

namespace Modules\AIAssistant\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\AIAssistant\Models\RoutingRule;
use App\Mailbox;

class RoutingRulesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $mailbox_id = $request->query('mailbox_id');
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $rules = RoutingRule::with('team')
            ->orderBy('priority')
            ->get();
        $teams = \Team::getTeams();

        return view('aiassistant::routing.index', compact('rules', 'teams', 'mailbox'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create(Request $request)
    {
        $mailbox_id = $request->query('mailbox_id');
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $teams = \Team::getTeams();
        return view('aiassistant::routing.create', compact('teams', 'mailbox'));
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $mailbox_id = $request->query('mailbox_id');
        
        $rule = new RoutingRule;
        $rule->name = $request->name;
        $rule->conditions = json_encode($request->conditions);
        $rule->priority = $request->priority;
        $rule->team_id = $request->team_id;
        $rule->required_skills = json_encode($request->required_skills);
        $rule->is_active = $request->is_active;
        $rule->save();

        return redirect()->route('routing.index', ['mailbox_id' => $mailbox_id]);
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id, Request $request)
    {
        $mailbox_id = $request->query('mailbox_id');
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $teams = \Team::getTeams();
        $rule = RoutingRule::find($id);
        return view('aiassistant::routing.edit', compact('teams', 'rule', 'mailbox'));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
        $mailbox_id = $request->query('mailbox_id');

        RoutingRule::find($request->id)->update([
            'name' => $request->name,
            'conditions' => json_encode($request->conditions),
            'priority' => $request->priority,
            'team_id' => $request->team_id,
            'required_skills' => json_encode($request->required_skills),
            'is_active' => $request->is_active,
        ]);
        return redirect()->route('routing.index', ['mailbox_id' => $mailbox_id]);
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy($id, Request $request)
    {
        RoutingRule::find($id)->delete();
        $mailbox_id = $request->query('mailbox_id');
        return redirect()->route('routing.index', ['mailbox_id' => $mailbox_id]);
    }
}
