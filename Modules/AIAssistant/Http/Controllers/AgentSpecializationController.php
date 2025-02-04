<?php

namespace Modules\AIAssistant\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\AIAssistant\Models\AgentSpecialization;
use Modules\AIAssistant\Models\User;

class AgentSpecializationController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $agents = User::with(['specializations'])->get();
        $allSkills = AgentSpecialization::distinct('skill')->pluck('skill');
        return view('aiassistant::specializations.index', compact('agents', 'allSkills'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create($id)
    {
        return view('aiassistant::specializations.create', ['agentId' => $id]);
    }
    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store($id, Request $request)
    {
        $newData = new AgentSpecialization;
        $newData->user_id = $id;
        $newData->skill = $request->skill;
        $newData->proficiency_level = $request->proficiency_level;
        $newData->is_primary = $request->is_primary;
        $newData->save();
        return redirect()->route('specializations.show', ['id' => $id]);
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show($id)
    {
        $agent = User::with(['specializations'])->find($id);
        return view('aiassistant::specializations.show', compact('agent'));
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id, Request $request)
    {
        $specId = $request->query('spec_id');

        $spec = AgentSpecialization::find($specId);
        return view('aiassistant::specializations.edit', ['agentId' => $id, 'spec' => $spec]);
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update($id, Request $request)
    {
        AgentSpecialization::find($request->id)->update([
            'skill' => $request->skill,
            'proficiency_level' => $request->proficiency_level,
            'is_primary' => $request->is_primary,
        ]);
        return redirect()->route('specializations.show', ['id' => $id]);
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
