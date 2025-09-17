<?php

namespace App\Http\Controllers;

use App\Models\TeamMembership;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\Team;

class TeamMembershipController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $membership = TeamMembership::create([
            'id' => (string) Str::uuid(),
            'team_id' => $request->input('teamId'),
            'user_id' => $request->input('memberId'),
            'role' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message'    => 'Join request created and is pending approval.',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TeamMembership $teamMembership)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TeamMembership $teamMembership)
    {

        $validated = $request->validate([
            'teamId' => ['required', 'uuid', Rule::exists('teams', 'id')],
            'role'   => ['required', Rule::in(['admin', 'member', 'rejected', 'pending'])],
        ]);

        $teamMembership->role = $validated['role'];
        $teamMembership->save();

        return response()->json([
            'success' => true,
            'message' => 'Membership atualizado com sucesso.',
            'data'    => $teamMembership,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TeamMembership $teamMembership)
    {
        //
    }
}
