<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $statuses = ['todo','doing','done'];
        $priorities = ['low','medium','high'];

        $due = $this->faker->optional()->dateTimeBetween('now', '+30 days');

        return [
            'title'       => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'due_date'    => $due ? $due->format('Y-m-d') : null,
            'status'      => $this->faker->randomElement($statuses),
            'priority'    => $this->faker->randomElement($priorities),
            'creator_id'  => User::factory(),
            'assignee_id' => null,
        ];
    }
}
