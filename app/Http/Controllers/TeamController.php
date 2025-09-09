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
            ->whereHas('memberships', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with('memberships')
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
                'icon'       => $data['icon'] ?? null,
                'created_by' => $user->id,
            ]);

            TeamMembership::create([
                'id'      => (string) Str::uuid(),
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role'    => 'creator',
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

        // garante que o user participa da equipe
        if (!$team->memberships()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Não autorizado'], 403);
        }

        return response()->json($team->load('memberships'));
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

        // só o creator pode deletar
        $membership = $team->memberships()->where('user_id', $user->id)->first();
        if (!$membership || $membership->role !== 'creator') {
            return response()->json(['error' => 'Não autorizado'], 403);
        }

        $team->delete();

        return response()->json(['message' => 'Equipe deletada']);
    }
}
