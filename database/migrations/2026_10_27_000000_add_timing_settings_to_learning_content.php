<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_minutes')->nullable();
        });

        Schema::table('test_results', function (Blueprint $table) {
            $table->timestamp('ends_at')->nullable();
        });

        Schema::table('challenges', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_minutes')->nullable();
        });

        Schema::table('challenge_submissions', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('deadline_at')->nullable();
            $table->boolean('allow_late_submissions')->default(false);
        });

        Schema::table('project_submissions', function (Blueprint $table) {
            $table->boolean('is_late')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('project_submissions', fn (Blueprint $table) => $table->dropColumn('is_late'));
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn(['deadline_at', 'allow_late_submissions']));
        Schema::table('challenge_submissions', fn (Blueprint $table) => $table->dropColumn(['started_at', 'ends_at']));
        Schema::table('challenges', fn (Blueprint $table) => $table->dropColumn('duration_minutes'));
        Schema::table('test_results', fn (Blueprint $table) => $table->dropColumn('ends_at'));
        Schema::table('tests', fn (Blueprint $table) => $table->dropColumn('duration_minutes'));
    }
};
