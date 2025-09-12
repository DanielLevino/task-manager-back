<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    /**
     * Listar todas as equipes do usuário autenticado
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $teams = Team::query()
            ->join('team_memberships as tm', function ($j) use ($user) {
                $j->on('tm.team_id', 'teams.id')
                    ->where('tm.user_id', $user->id);
            })
            ->select('teams.id', 'teams.name', 'tm.role as member_role')
            ->get();

        return response()->json($teams);
    }

    /**
     * Criar nova equipe
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($data, $user) {
            $team = Team::create([
                'id'         => (string) Str::uuid(),
                'name'       => $data['name'],
                //'icon'       => $data['icon'] ?? null,
                'created_by' => $user->id,
            ]);

            TeamMembership::create([
                'id'      => (string) Str::uuid(),
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role'    => 'owner',
            ]);

            return response()->json([
                'message' => 'Equipe criada com sucesso',
                'data'    => $team,
            ], 201);
        });
    }

    /**
     * Mostrar uma equipe específica
     */
    public function show(Team $team, Request $request)
    {
        $user = $request->user();

        if (!$team->memberships()->where('user_id', $user->id)->exists()) {
            return response()->json([
                "id"=>$team->id, 
                "name"=>$team->name,
                "member"=> false,
                "approval"=>false,
            ],203);
        }

        $team->load([
            'memberships' => function ($q) {
                $q->select('id', 'team_id', 'user_id', 'role');
            },
            'memberships.user:id,name,email',
        ]);

        $myRole = optional(
            $team->memberships->firstWhere('user_id', $user->id)
        )->role;

        if($myRole=="pending"){
            return response()->json([
                "id"=>$team->id, 
                "name"=>$team->name,
                "member"=> false,
                "approval"=>"pending",
            ],203);
        }

        if($myRole=="rejected"){
            return response()->json([
                "id"=>$team->id, 
                "name"=>$team->name,
                "member"=> false,
                "approval"=>"rejected",
            ],203);
        }

        $response = [
            'id'        => $team->id,
            'name'      => $team->name,
            'member'    => true,
            'my_role'   => $myRole,
            'memberships' => $team->memberships->map(function ($membership) {
                return [
                    'id'    => $membership->user->id,
                    'name'  => $membership->user->name,
                    'email' => $membership->user->email,
                    'role'  => $membership->role,
                ];
            })->values(),
        ];

        return response()->json($response, 200);
    }

    /**
     * Atualizar uma equipe
     */
    public function update(Request $request, Team $team)
    {
        $user = $request->user();

        // só admin/creator pode atualizar
        $membership = $team->memberships()->where('user_id', $user->id)->first();
        if (!$membership || !in_array($membership->role, ['admin', 'creator'])) {
            return response()->json(['error' => 'Não autorizado'], 403);
        }

        $data = $request->validate([
            'name' => ['string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
        ]);

        $team->update($data);

        return response()->json([
            'message' => 'Equipe atualizada',
            'data'    => $team,
        ]);
    }

    /**
     * Deletar uma equipe
     */
    public function destroy(Team $team, Request $request)
    {
        $user = $request->user();

        $membership = $team->memberships()->where('user_id', $user->id)->first();
        if (!$membership || $membership->role !== 'creator') {
            return response()->json(['error' => 'Não autorizado'], 403);
        }

        $team->delete();

        return response()->json(['message' => 'Equipe deletada']);
    }
}
