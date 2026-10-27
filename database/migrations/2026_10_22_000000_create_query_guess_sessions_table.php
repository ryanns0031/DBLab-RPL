<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('query_guess_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->json('question_order');
            $table->json('answers');
            $table->unsignedSmallInteger('score')->default(0);
            $table->unsignedTinyInteger('correct_count')->default(0);
            $table->unsignedTinyInteger('wrong_count')->default(0);
            $table->unsignedTinyInteger('current_index')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('query_guess_sessions');
    }
};
