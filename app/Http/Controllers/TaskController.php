<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /**
     * GET /api/tasks
     * Filtros: status, priority, assignee_id, q (busca no título/descrição),
     *          due_before=YYYY-MM-DD, due_after=YYYY-MM-DD
     * Ordenação: sort=created_at|due_date|priority|status (default: -created_at)
     * Páginação: per_page (default 20)
     */
    public function index(Request $req)
    {
        $user = $req->user();

        $q = Task::query()
            ->with(['creator:id,name', 'assignee:id,name']);

        $view = $req->query('view', 'mine'); // mine | all
        if (!($view === 'all' && $user->is_admin)) {
            $q->visibleTo($user);
        }

        // --- filtros existentes ---
        if ($status = $req->query('status')) {
            $q->where('status', $status);
        }
        if ($priority = $req->query('priority')) {
            $q->where('priority', $priority);
        }
        if ($assigneeId = $req->query('assignee_id')) {
            $q->where('assignee_id', $assigneeId);
        }
        if ($term = $req->query('q')) {
            $q->where(function ($w) use ($term) {
                $w->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
            });
        }
        if ($dueAfter = $req->query('due_after')) {
            $q->whereDate('due_date', '>=', $dueAfter);
        }
        if ($dueBefore = $req->query('due_before')) {
            $q->whereDate('due_date', '<=', $dueBefore);
        }

        $sort = $req->query('sort', '-created_at');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $allowedSorts = ['created_at', 'due_date', 'priority', 'status'];
        if (! in_array($column, $allowedSorts, true)) {
            $column = 'created_at';
            $direction = 'desc';
        }
        $q->orderBy($column, $direction);

        $perPage = (int) $req->query('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        return $q->paginate($perPage);
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'nullable|date',
            'status'      => ['nullable', Rule::in(['todo','doing','done'])],
            'priority'    => ['nullable', Rule::in(['low','medium','high'])],
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        $data['creator_id'] = $req->user()->id;

        $task = Task::create($data);

        return response()->json(
            $task->load(['creator:id,name', 'assignee:id,name']),
            201
        );
    }

    /**
     * GET /api/tasks/{task}
     */
    public function show(Task $task)
    {
        // se quiser, futuras regras de acesso entram aqui
        return $task->load(['creator:id,name', 'assignee:id,name']);
    }

    /**
     * PUT/PATCH /api/tasks/{task}
     */
    public function update(Request $req, Task $task)
    {
        $this->authorizeUpdate($req, $task);

        $data = $req->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'nullable|date',
            'status'      => ['nullable', Rule::in(['todo','doing','done'])],
            'priority'    => ['nullable', Rule::in(['low','medium','high'])],
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        $task->update($data);

        return $task->load(['creator:id,name', 'assignee:id,name']);
    }

    /**
     * DELETE /api/tasks/{task}
     */
    public function destroy(Request $req, Task $task)
    {
        $this->authorizeUpdate($req, $task);
        $task->delete();
        return response()->noContent();
    }

    /**
     * POST /api/tasks/{task}/assign
     * body: { assignee_id: number }
     */
    public function assign(Request $req, Task $task)
    {
        $this->authorizeUpdate($req, $task);

        $data = $req->validate([
            'assignee_id' => 'required|exists:users,id',
        ]);

        $task->update(['assignee_id' => $data['assignee_id']]);

        return $task->load(['creator:id,name', 'assignee:id,name']);
    }

    /**
     * Regra simples: só criador OU atual responsável podem atualizar/excluir/atribuir.
     * (Depois podemos mover para Policies)
     */
    protected function authorizeUpdate(Request $req, Task $task): void
    {
        $uid = $req->user()->id;
        if ($uid !== (int) $task->creator_id && $uid !== (int) $task->assignee_id) {
            abort(403, 'Sem permissão para alterar esta tarefa.');
        }
    }
}
