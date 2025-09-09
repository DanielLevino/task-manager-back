<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('team_id');
            $table->uuid('user_id');

            // Papel do usuário na equipe
            $table->string('role')->default('member'); 
            // valores esperados: 'creator', 'admin', 'member'

            $table->string('status')->default('pendding');
            // valores esperados: 'pendding', '

            $table->timestamps();

            // Relacionamentos
            $table->foreign('team_id')
                  ->references('id')
                  ->on('teams')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Evita duplicidade de usuário na mesma equipe
            $table->unique(['team_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_memberships');
    }
};
