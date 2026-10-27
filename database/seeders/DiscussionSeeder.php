<?php

namespace Database\Seeders;

use App\Models\Discussion;
use App\Models\DiscussionCategory;
use App\Models\DiscussionReply;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DiscussionSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'SQL Dasar', 'slug' => 'sql-dasar', 'description' => 'Konsep awal, sintaks umum, dan pemahaman database.', 'color' => 'blue', 'sort_order' => 1],
            ['name' => 'SELECT dan WHERE', 'slug' => 'select-dan-where', 'description' => 'Kueri pengambilan data dan penyaringan kondisi.', 'color' => 'teal', 'sort_order' => 2],
            ['name' => 'JOIN dan Relasi', 'slug' => 'join-dan-relasi', 'description' => 'Menghubungkan banyak tabel: INNER, LEFT, RIGHT JOIN.', 'color' => 'purple', 'sort_order' => 3],
            ['name' => 'Database Design', 'slug' => 'database-design', 'description' => 'Perancangan ERD, normalisasi, Primary dan Foreign Key.', 'color' => 'indigo', 'sort_order' => 4],
            ['name' => 'Project Database', 'slug' => 'project-database', 'description' => 'Diskusi pengerjaan project kelompok dan tugas mandiri.', 'color' => 'amber', 'sort_order' => 5],
            ['name' => 'Error dan Debugging', 'slug' => 'error-dan-debugging', 'description' => 'Bantuan mengatasi pesan error SQL sandbox dan MySQL.', 'color' => 'rose', 'sort_order' => 6],
            ['name' => 'Tanya Jawab Umum', 'slug' => 'tanya-jawab-umum', 'description' => 'Pertanyaan seputar tips belajar, tools, dan basis data.', 'color' => 'cyan', 'sort_order' => 7],
        ];

        foreach ($categories as $cat) {
            DiscussionCategory::firstOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
        }

        // Add sample discussions if students exist
        $student = User::where('role', 'siswa')->first();
        $otherStudent = User::where('role', 'siswa')->skip(1)->first() ?? $student;

        if ($student) {
            $catJoin = DiscussionCategory::where('slug', 'join-dan-relasi')->first();
            $d1 = Discussion::firstOrCreate(
                ['slug' => 'perbedaan-inner-join-dan-left-join-pada-tabel-pesanan'],
                [
                    'user_id' => $student->id,
                    'discussion_category_id' => $catJoin->id,
                    'title' => 'Perbedaan INNER JOIN dan LEFT JOIN pada tabel pesanan?',
                    'content' => 'Halo teman-teman, saya sedang mengerjakan challenge di SQL Lab. Kapan sebaiknya kita menggunakan INNER JOIN dibanding LEFT JOIN saat ingin menampilkan data pelanggan dan riwayat pesanannya?',
                    'sql_snippet' => "SELECT pelanggan.nama, pesanan.total\nFROM pelanggan\nLEFT JOIN pesanan ON pelanggan.id = pesanan.pelanggan_id;",
                    'status' => 'terjawab',
                    'views_count' => 14,
                    'replies_count' => 1,
                ]
            );

            if ($otherStudent && $d1->replies()->count() === 0) {
                $r1 = DiscussionReply::create([
                    'discussion_id' => $d1->id,
                    'user_id' => $otherStudent->id,
                    'content' => "Gunakan **INNER JOIN** jika kamu hanya ingin menampilkan pelanggan yang *pernah* berbelanja saja. Sedangkan jika kamu ingin semua pelanggan tetap muncul (walaupun belum pernah belanja, totalnya NULL), gunakan **LEFT JOIN**.",
                    'sql_snippet' => "-- Pelanggan tanpa pesanan tetap muncul dengan NULL\nSELECT p.nama, o.id AS id_pesanan\nFROM pelanggan p\nLEFT JOIN pesanan o ON p.id = o.pelanggan_id;",
                    'is_solution' => true,
                ]);

                $d1->update(['solved_reply_id' => $r1->id]);
            }

            $catError = DiscussionCategory::where('slug', 'error-dan-debugging')->first();
            Discussion::firstOrCreate(
                ['slug' => 'mengapa-query-agregasi-count-saya-error-invalid-group-by'],
                [
                    'user_id' => $student->id,
                    'discussion_category_id' => $catError->id,
                    'title' => 'Mengapa query agregasi COUNT saya error invalid GROUP BY?',
                    'content' => 'Saya ingin menghitung jumlah siswa di setiap kelas, tetapi muncul error ketika memilih nama siswa bersamaan dengan COUNT(). Apakah ada kolom yang terlewat pada klausa GROUP BY?',
                    'sql_snippet' => "SELECT kelas, COUNT(id) AS total\nFROM siswa\nGROUP BY kelas;",
                    'status' => 'belum_terjawab',
                    'views_count' => 6,
                    'replies_count' => 0,
                ]
            );
        }
    }
}

