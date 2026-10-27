<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $year = AcademicYear::firstOrCreate(['name' => '2026/2027'], ['semester' => 'Ganjil', 'active' => true]);
        $classOne = SchoolClass::firstOrCreate(['name' => 'XI RPL 1'], ['academic_year_id' => $year->id, 'level' => 'XI', 'status' => 'aktif']);
        $classTwo = SchoolClass::firstOrCreate(['name' => 'XI RPL 2'], ['academic_year_id' => $year->id, 'level' => 'XI', 'status' => 'aktif']);

        User::firstOrCreate(
            ['email' => strtolower(env('ADMIN_EMAIL', 'admin.dblab@gmail.com'))],
            ['name' => 'Administrator DBLab', 'role' => 'admin', 'status' => 'aktif'],
        );
        $teacher = User::firstOrCreate(
            ['email' => 'guru.dblab@gmail.com'],
            ['name' => 'Ibu Sari', 'role' => 'guru', 'status' => 'aktif'],
        );
        $teacher->classes()->syncWithoutDetaching([$classOne->id, $classTwo->id]);

        foreach ([
            ['Rizky', 'siswa.dblab@gmail.com', '20260123'],
            ['Andi', 'andi@example.com', '20260124'],
            ['Rina', 'rina@example.com', '20260125'],
            ['Budi', 'budi@example.com', '20260126'],
        ] as [$name, $email, $nis]) {
            $student = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'role' => 'siswa', 'status' => 'aktif', 'nis' => $nis, 'class_id' => $classOne->id],
            );
            DB::table('class_students')->updateOrInsert(
                ['class_id' => $classOne->id, 'student_id' => $student->id],
                ['created_at' => now(), 'updated_at' => now()],
            );
        }

        $this->call(StudentLearningContentSeeder::class);
        $this->call(DatabaseFunFactsSeeder::class);
        $this->call(DiscussionSeeder::class);
        $this->call(ProjectShowcaseSeeder::class);
    }
}
