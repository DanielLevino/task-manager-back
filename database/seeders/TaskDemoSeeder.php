<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TaskDemoSeeder extends Seeder
{
    public function run(): void
    {
        // cria dois usuários de teste
        $alice = User::firstOrCreate(
            ['email' => 'alice@example.com'],
            ['name' => 'Alice', 'password' => Hash::make('password')]
        );

        $bob = User::firstOrCreate(
            ['email' => 'bob@example.com'],
            ['name' => 'Bob', 'password' => Hash::make('password')]
        );

        // 10 tarefas criadas por Alice, atribuídas opcionalmente ao Bob
        Task::factory()
            ->count(10)
            ->create()
            ->each(function ($task) use ($alice, $bob) {
                $task->creator_id = $alice->id;
                if (rand(0, 1)) {
                    $task->assignee_id = $bob->id;
                }
                $task->save();
            });
    }
}
