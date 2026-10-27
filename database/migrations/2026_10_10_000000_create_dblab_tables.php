<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('semester')->default('Ganjil');
            $t->boolean('active')->default(false);
            $t->timestamps();
        });
        Schema::create('classes', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('level')->default('XI');
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->string('status')->default('aktif');
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('google_id')->nullable()->unique();
            $t->string('name');
            $t->string('email')->unique();
            $t->text('avatar')->nullable();
            $t->string('role')->index();
            $t->string('status')->default('aktif');
            $t->string('nis')->nullable()->unique();
            $t->string('nip')->nullable();
            $t->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $t->timestamp('email_verified_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('class_students', function (Blueprint $t) {
            $t->id();
            $t->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->unique(['class_id', 'student_id']);
            $t->timestamps();
        });
        Schema::create('teacher_classes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $t->unique(['teacher_id', 'class_id']);
            $t->timestamps();
        });
        Schema::create('materials', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('description');
            $t->longText('content');
            $t->string('file_path')->nullable();
            $t->string('original_name')->nullable();
            $t->unsignedInteger('week');
            $t->unsignedInteger('sort_order')->default(1);
            $t->string('status')->default('aktif');
            $t->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('material_progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('material_id')->constrained()->cascadeOnDelete();
            $t->unique(['student_id', 'material_id']);
            $t->timestamps();
        });
        Schema::create('tests', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('type');
            $t->string('status')->default('aktif');
            $t->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('test_id')->constrained()->cascadeOnDelete();
            $t->text('question');
            $t->text('option_a');
            $t->text('option_b');
            $t->text('option_c');
            $t->text('option_d');
            $t->string('correct_answer', 1);
            $t->timestamps();
        });
        Schema::create('test_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('test_id')->constrained()->cascadeOnDelete();
            $t->decimal('score', 5, 2)->nullable();
            $t->json('snapshot');
            $t->timestamp('started_at');
            $t->timestamp('submitted_at')->nullable();
            $t->unique(['student_id', 'test_id']);
            $t->timestamps();
        });
        Schema::create('test_answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('test_result_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('question_id');
            $t->string('answer', 1);
            $t->boolean('correct');
            $t->unique(['test_result_id', 'question_id']);
            $t->timestamps();
        });
        Schema::create('challenges', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->foreignId('material_id')->constrained()->restrictOnDelete();
            $t->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $t->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $t->text('description');
            $t->string('difficulty');
            $t->text('expected_query');
            $t->json('expected_result');
            $t->unsignedInteger('points')->default(100);
            $t->string('status')->default('aktif');
            $t->timestamps();
        });
        Schema::create('challenge_submissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $t->text('query');
            $t->string('status');
            $t->unsignedInteger('score')->default(0);
            $t->unsignedInteger('attempt')->default(1);
            $t->timestamp('completed_at')->nullable();
            $t->unique(['student_id', 'challenge_id']);
            $t->timestamps();
        });
        Schema::create('projects', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('description');
            $t->unsignedInteger('week');
            $t->date('deadline');
            $t->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $t->string('status')->default('aktif');
            $t->timestamps();
        });
        Schema::create('project_steps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->unsignedInteger('sort_order')->default(1);
            $t->timestamps();
        });
        Schema::create('project_submissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->text('notes');
            $t->string('file_path')->nullable();
            $t->string('original_name')->nullable();
            $t->unsignedInteger('score')->nullable();
            $t->text('feedback')->nullable();
            $t->unique(['student_id', 'project_id']);
            $t->timestamps();
        });
        Schema::create('student_project_steps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('project_step_id')->constrained()->cascadeOnDelete();
            $t->unique(['student_id', 'project_step_id']);
            $t->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('description');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('student_project_steps');
        Schema::dropIfExists('project_submissions');
        Schema::dropIfExists('project_steps');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('challenge_submissions');
        Schema::dropIfExists('challenges');
        Schema::dropIfExists('test_answers');
        Schema::dropIfExists('test_results');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('tests');
        Schema::dropIfExists('material_progress');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('teacher_classes');
        Schema::dropIfExists('class_students');
        Schema::dropIfExists('users');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('academic_years');
    }
};
