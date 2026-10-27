<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_fun_facts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->index();
            $table->text('summary');
            $table->longText('content');
            $table->text('real_world_example')->nullable();
            $table->text('database_concept')->nullable();
            $table->text('sql_connection')->nullable();
            $table->string('illustration_type')->default('database');
            $table->string('source_reference')->nullable();
            $table->string('status')->default('aktif')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('database_fun_fact_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('database_fun_fact_id')->constrained('database_fun_facts')->cascadeOnDelete();
            $table->timestamp('first_read_at');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'database_fun_fact_id']);
            $table->index(['student_id', 'database_fun_fact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_fun_fact_reads');
        Schema::dropIfExists('database_fun_facts');
    }
};

