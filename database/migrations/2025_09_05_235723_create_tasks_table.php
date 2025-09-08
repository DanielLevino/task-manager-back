<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();

            $table->date('due_date')->nullable();

            // Em SQLite o enum vira TEXT com CHECK internamente (Laravel cuida)
            $table->enum('status', ['todo','doing','done'])->default('todo');
            $table->enum('priority', ['low','medium','high'])->default('medium');

            // Relacionamentos básicos
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Índices úteis para filtros
            $table->index(['status', 'priority']);
            $table->index('due_date');
            $table->index('assignee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
