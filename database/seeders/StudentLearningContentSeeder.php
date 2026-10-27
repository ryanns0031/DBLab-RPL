<?php

namespace Database\Seeders;

use App\Models\Challenge;
use App\Models\LearningTest;
use App\Models\Material;
use App\Models\Project;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentLearningContentSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            ['Perintah SQL untuk mengambil data adalah?', 'SELECT', 'DELETE', 'DROP', 'ALTER', 'A'],
            ['Kolom yang membedakan setiap baris secara unik disebut?', 'Foreign key', 'Primary key', 'Record', 'Query', 'B'],
            ['Klausa untuk menyaring baris adalah?', 'FROM', 'JOIN', 'WHERE', 'CREATE', 'C'],
            ['Fungsi agregat untuk menghitung jumlah baris adalah?', 'SUM', 'AVG', 'MIN', 'COUNT', 'D'],
            ['Relasi satu pelanggan dengan banyak pesanan disebut?', 'Satu-ke-banyak', 'Satu-ke-satu', 'Tidak berelasi', 'Selalu banyak-ke-banyak', 'A'],
            ['Klausa yang menentukan tabel sumber data adalah?', 'SELECT', 'FROM', 'ORDER BY', 'LIMIT', 'B'],
            ['Klausa untuk mengurutkan hasil query adalah?', 'GROUP BY', 'WHERE', 'ORDER BY', 'HAVING', 'C'],
            ['Perintah untuk menggabungkan baris dari tabel terkait adalah?', 'UNION', 'UPDATE', 'CREATE', 'JOIN', 'D'],
            ['Fungsi yang menghitung nilai rata-rata adalah?', 'AVG', 'COUNT', 'SUM', 'MAX', 'A'],
            ['Klausa untuk mengelompokkan baris berdasarkan kolom adalah?', 'ORDER BY', 'GROUP BY', 'WHERE', 'FROM', 'B'],
            ['Kunci yang merujuk primary key pada tabel lain disebut?', 'Candidate key', 'Super key', 'Foreign key', 'Composite key', 'C'],
            ['Apa hasil dari SELECT nama FROM siswa LIMIT 3?', 'Tiga kolom pertama', 'Tiga tabel siswa', 'Semua baris siswa', 'Maksimal tiga baris siswa', 'D'],
            ['Urutan menaik pada ORDER BY ditulis dengan kata?', 'ASC', 'DESC', 'UP', 'FIRST', 'A'],
            ['Perintah untuk menambahkan baris baru ke tabel adalah?', 'ALTER', 'INSERT', 'UPDATE', 'DROP', 'B'],
            ['Klausa yang menyaring kelompok hasil agregasi adalah?', 'WHERE', 'ORDER BY', 'HAVING', 'LIMIT', 'C'],
        ];

        $lessons = [
            ['Konsep Dasar Basis Data', 'Kenali cara menyimpan data agar terstruktur.', 'Basis data adalah kumpulan data yang dikelola secara terstruktur. DBMS membantu menyimpan, mengambil, dan menjaga konsistensi data. Contoh: data siswa di sekolah.'],
            ['Tabel, Field, dan Record', 'Pahami komponen utama database relasional.', 'Tabel mengelompokkan data sejenis. Field atau kolom menyatakan atribut. Record atau baris merupakan satu entri. Primary key membedakan setiap baris secara unik.'],
            ['ERD, Primary Key, dan Foreign Key', 'Rancang entitas dan hubungan antar tabel.', 'Entity merupakan objek yang datanya disimpan. Primary key mengidentifikasi baris secara unik, sedangkan foreign key menghubungkan satu tabel dengan tabel lain.'],
            ['SELECT dan WHERE', 'Ambil dan saring data sesuai kebutuhan.', "SELECT menentukan kolom, FROM menentukan tabel, dan WHERE menyaring baris.\n\nSELECT nama FROM siswa WHERE kelas = 'XI RPL 1';"],
            ['JOIN, GROUP BY, dan Fungsi Agregat', 'Gabungkan dan ringkas data dari beberapa tabel.', 'JOIN menggabungkan tabel terkait. COUNT, SUM, dan AVG meringkas data. GROUP BY membentuk kelompok sebelum nilai agregat dihitung.'],
        ];

        $challengeDefinitions = [
            [
                'title' => 'Pelanggan dari Surabaya',
                'material_week' => 4,
                'description' => 'Tampilkan kolom nama pelanggan yang berasal dari Surabaya.',
                'difficulty' => 'Mudah',
                'expected_query' => "SELECT nama FROM pelanggan WHERE kota = 'Surabaya'",
                'expected_result' => [['nama' => 'Nina'], ['nama' => 'Dimas']],
                'points' => 100,
            ],
            [
                'title' => 'Pesanan Pelanggan Surabaya',
                'material_week' => 5,
                'description' => 'Gabungkan tabel pelanggan dan pesanan. Tampilkan nama pelanggan serta total pesanannya untuk pelanggan dari Surabaya.',
                'difficulty' => 'Menengah',
                'expected_query' => "SELECT pelanggan.nama, pesanan.total FROM pelanggan JOIN pesanan ON pelanggan.id = pesanan.pelanggan_id WHERE pelanggan.kota = 'Surabaya'",
                'expected_result' => [['nama' => 'Nina', 'total' => 150000], ['nama' => 'Dimas', 'total' => 200000], ['nama' => 'Nina', 'total' => 75000]],
                'points' => 150,
            ],
        ];

        foreach (SchoolClass::where('status', 'aktif')->orderBy('id')->get() as $class) {
            $teacher = $class->teachers()->where('role', 'guru')->first() ?? User::where('role', 'guru')->orderBy('id')->first();
            if (! $teacher) {
                continue;
            }

            $activeMaterials = Material::where('class_id', $class->id)->where('status', 'aktif')->count();
            foreach ($lessons as $index => [$title, $description, $content]) {
                if ($activeMaterials >= 5) {
                    break;
                }
                if (Material::where('class_id', $class->id)->where('title', $title)->where('status', 'aktif')->exists()) {
                    continue;
                }

                Material::create([
                    'class_id' => $class->id,
                    'teacher_id' => $teacher->id,
                    'week' => $index + 1,
                    'sort_order' => $index + 1,
                    'title' => $title,
                    'description' => $description,
                    'content' => $content,
                    'status' => 'aktif',
                ]);
                $activeMaterials++;
            }

            foreach (['pretest' => 'Pre-test', 'posttest' => 'Post-test'] as $type => $label) {
                $test = LearningTest::firstOrCreate(
                    ['class_id' => $class->id, 'type' => $type],
                    ['title' => $label.' Basis Data', 'teacher_id' => $teacher->id, 'status' => 'aktif'],
                );

                foreach ($questions as [$question, $a, $b, $c, $d, $answer]) {
                    if ($test->questions()->count() >= 15) {
                        break;
                    }
                    $test->questions()->firstOrCreate(
                        ['question' => $question],
                        ['option_a' => $a, 'option_b' => $b, 'option_c' => $c, 'option_d' => $d, 'correct_answer' => $answer],
                    );
                }
            }

            foreach ($challengeDefinitions as $definition) {
                if (Challenge::where('class_id', $class->id)->where('status', 'aktif')->count() >= 2) {
                    break;
                }
                $material = Material::where('class_id', $class->id)
                    ->where('status', 'aktif')
                    ->where('week', $definition['material_week'])
                    ->first() ?? Material::where('class_id', $class->id)->where('status', 'aktif')->orderBy('week')->first();
                if (! $material) {
                    continue;
                }

                if (Challenge::where('class_id', $class->id)->where('title', $definition['title'])->where('status', 'aktif')->exists()) {
                    continue;
                }

                Challenge::create([
                    'title' => $definition['title'],
                    'description' => $definition['description'],
                    'difficulty' => $definition['difficulty'],
                    'expected_query' => $definition['expected_query'],
                    'expected_result' => $definition['expected_result'],
                    'points' => $definition['points'],
                    'class_id' => $class->id,
                    'material_id' => $material->id,
                    'teacher_id' => $teacher->id,
                    'status' => 'aktif',
                ]);
            }

            $hasActiveProject = Project::where('class_id', $class->id)->where('status', 'aktif')->exists();
            if (! $hasActiveProject) {
                $project = Project::firstOrCreate(
                    ['class_id' => $class->id, 'title' => 'Database Minimarket'],
                    [
                        'description' => 'Rancang database minimarket dari identifikasi entitas sampai laporan penjualan. Kumpulkan ERD dan skrip SQL dalam satu ZIP.',
                        'week' => 5,
                        'deadline' => now()->addWeeks(12)->toDateString(),
                        'teacher_id' => $teacher->id,
                        'status' => 'aktif',
                    ],
                );
                foreach (['Identifikasi entitas', 'Buat ERD', 'Struktur tabel', 'Primary key', 'Foreign key', 'Input data', 'Query laporan', 'Presentasi'] as $index => $step) {
                    $project->steps()->firstOrCreate(['sort_order' => $index + 1], ['title' => $step]);
                }
            }
        }
    }
}
