<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('query_detective_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->json('case_order');
            $table->json('case_progress');
            $table->unsignedSmallInteger('score')->default(0);
            $table->unsignedTinyInteger('solved_count')->default(0);
            $table->unsignedTinyInteger('current_index')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('query_detective_sessions');
    }
};
