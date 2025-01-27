<?php

namespace Modules\AIAssistant\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\AIAssistant\Models\AgentSpecialization;
use Modules\AIAssistant\Models\User;
use App\Mailbox;

class AgentSpecializationController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $mailbox_id = $request->query('mailbox_id');
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $agents = User::with(['specializations'])->get();
        $allSkills = AgentSpecialization::distinct('skill')->pluck('skill');
        return view('aiassistant::specializations.index', compact('agents', 'allSkills', 'mailbox'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create($id, Request $request) {
        $mailbox_id = $request->query('mailbox_id');
        $mailbox = Mailbox::findOrFail($mailbox_id);

        return view('aiassistant::specializations.create', ['agentId' => $id, 'mailbox' => $mailbox]);
    }
    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store($id, Request $request)
    {
        $mailbox_id = $request->query('mailbox_id');
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $newData = new AgentSpecialization;
        $newData->user_id = $id;
        $newData->skill = $request->skill;
        $newData->proficiency_level = $request->proficiency_level;
        $newData->is_primary = $request->is_primary;
        $newData->save();
        return redirect()->route('specializations.show', ['id' => $id, 'mailbox_id' => $mailbox_id]);
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show($id, Request $request)
    {
        $mailbox_id = $request->query('mailbox_id');
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $agent = User::with(['specializations'])->find($id);
        return view('aiassistant::specializations.show', compact('agent', 'mailbox'));
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id, Request $request)
    {
        $mailbox_id = $request->query('mailbox_id');
        $specId = $request->query('spec_id');
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $spec = AgentSpecialization::find($specId);
        return view('aiassistant::specializations.edit', ['agentId' => $id, 'spec' => $spec, 'mailbox' => $mailbox]);
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update($id, Request $request)
    {
        $mailbox_id = $request->query('mailbox_id');

        AgentSpecialization::find($request->id)->update([
            'skill' => $request->skill,
            'proficiency_level' => $request->proficiency_level,
            'is_primary' => $request->is_primary,
        ]);
        return redirect()->route('specializations.show', ['id' => $id, 'mailbox_id' => $mailbox_id]);
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy($id)
    {
        AgentSpecialization::find($id)->delete();
        return redirect()->back();
    }
}
