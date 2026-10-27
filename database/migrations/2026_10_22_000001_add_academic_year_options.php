<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (['2024/2025', '2025/2026', '2026/2027'] as $name) {
            if (! DB::table('academic_years')->where('name', $name)->exists()) {
                DB::table('academic_years')->insert([
                    'name' => $name,
                    'semester' => 'Ganjil',
                    'active' => $name === '2026/2027',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('academic_years')
            ->whereIn('name', ['2024/2025', '2025/2026', '2026/2027'])
            ->whereNotIn('id', DB::table('classes')->select('academic_year_id'))
            ->delete();
    }
};
