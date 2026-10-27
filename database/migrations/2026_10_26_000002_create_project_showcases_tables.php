<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_showcases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->index();
            $table->text('short_description');
            $table->longText('description');
            $table->text('purpose')->nullable();
            $table->text('problem_solved')->nullable();
            $table->text('features')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('erd_path')->nullable();
            $table->json('database_structure')->nullable(); // tables, columns, relations
            $table->text('sql_examples')->nullable();
            $table->text('challenges_and_solutions')->nullable();
            $table->text('lessons_learned')->nullable();
            $table->text('future_improvements')->nullable();
            $table->string('status')->default('menunggu_review')->index(); // draft, menunggu_review, disetujui, perlu_revisi, ditolak
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
        });

        Schema::create('project_showcase_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_showcase_id')->constrained('project_showcases')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'project_showcase_id']);
        });

        Schema::create('project_showcase_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_showcase_id')->constrained('project_showcases')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'project_showcase_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_showcase_favorites');
        Schema::dropIfExists('project_showcase_likes');
        Schema::dropIfExists('project_showcases');
    }
};

