<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('materials', 'youtube_url')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->string('youtube_url', 2048)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('materials', 'youtube_url')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->dropColumn('youtube_url');
            });
        }
    }
};
