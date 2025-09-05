<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        return response()->json(['tasks' => []]);
    }

    public function store(Request $request)
    {
        return response()->json(['created' => true], 201);
    }

    public function show($id)
    {
        return response()->json(['task' => ['id' => $id]]);
    }

    public function update(Request $request, $id)
    {
        return response()->json(['updated' => true]);
    }

    public function destroy($id)
    {
        return response()->noContent();
    }
}
