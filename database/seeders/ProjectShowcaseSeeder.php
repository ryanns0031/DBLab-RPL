<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectShowcase;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::where('role', 'siswa')->first();
        $teacher = User::where('role', 'guru')->first();
        $project = Project::first();

        if ($student) {
            $showcases = [
                [
                    'student_id' => $student->id,
                    'project_id' => $project?->id,
                    'title' => 'Sistem Perpustakaan Digital',
                    'slug' => 'sistem-perpustakaan-digital',
                    'category' => 'Perpustakaan',
                    'thumbnail_path' => 'showcase/thumbnails/perpustakaan-digital-dashboard.png',
                    'short_description' => 'Aplikasi database untuk mengelola buku, anggota, peminjaman, dan pengembalian buku dengan pencatatan denda otomatis.',
                    'description' => "Project ini dibangun untuk mendigitalisasi pengelolaan sirkulasi buku pada perpustakaan sekolah. Sistem menyimpan katalog buku dengan ISBN, kategori, data anggota siswa, serta transaksi peminjaman lengkap dengan tanggal jatuh tempo.\n\nDengan desain database relasional yang terstruktur, petugas perpustakaan dapat melacak ketersediaan stok buku secara real-time dan mengetahui daftar peminjam yang terlambat mengembalikan buku.",
                    'purpose' => 'Mempermudah staf perpustakaan dalam mengontrol inventaris buku dan mengurangi risiko kehilangan buku pinjaman.',
                    'problem_solved' => 'Pencatatan manual di buku besar seringkali tidak sinkron dengan stok fisik di rak dan menyulitkan rekap denda tahunan.',
                    'features' => "- Manajemen Katalog Buku & Kategori\n- Pendaftaran Anggota & Kartu Digital\n- Transaksi Peminjaman & Pengembalian\n- Penghitungan Denda Otomatis Berdasarkan Hari Keterlambatan\n- Rekap Buku Terfavorit",
                    'database_structure' => [
                        'tables' => ['books', 'members', 'loans', 'loan_details', 'categories'],
                        'relations' => [
                            'books.category_id -> categories.id (Many to One)',
                            'loans.member_id -> members.id (Many to One)',
                            'loan_details.loan_id -> loans.id (Many to One)',
                            'loan_details.book_id -> books.id (Many to One)',
                        ],
                    ],
                    'sql_examples' => "SELECT b.judul, m.nama AS peminjam, l.tanggal_pinjam, l.jatuh_tempo\nFROM loan_details ld\nJOIN loans l ON ld.loan_id = l.id\nJOIN members m ON l.member_id = m.id\nJOIN books b ON ld.book_id = b.id\nWHERE ld.status = 'dipinjam' AND l.jatuh_tempo < CURRENT_DATE();",
                    'challenges_and_solutions' => 'Tantangan utama adalah menangani pengembalian sebagian ketika siswa meminjam 3 buku sekaligus namun hanya mengembalikan 1. Solusinya adalah memisahkan status pengembalian pada tabel rincian (loan_details).',
                    'lessons_learned' => 'Pentingnya foreign key constraint CASCADE vs RESTRICT agar data riwayat peminjaman historis tidak terhapus jika buku dihapus dari katalog.',
                    'future_improvements' => 'Menambahkan notifikasi email otomatis ke siswa 1 hari sebelum batas waktu peminjaman habis.',
                    'status' => ProjectShowcase::STATUS_APPROVED,
                    'reviewed_by' => $teacher?->id,
                    'reviewed_at' => now()->subDays(2),
                    'published_at' => now()->subDays(2),
                    'likes_count' => 5,
                    'views_count' => 28,
                ],
                [
                    'student_id' => $student->id,
                    'project_id' => null,
                    'title' => 'Sistem Inventaris Laboratorium RPL',
                    'slug' => 'sistem-inventaris-laboratorium-rpl',
                    'category' => 'Inventaris',
                    'thumbnail_path' => 'showcase/thumbnails/inventaris-laboratorium-rpl-dashboard.png',
                    'short_description' => 'Sistem pencatatan aset PC, router, dan periferal lab RPL lengkap dengan riwayat servis berkala.',
                    'description' => "Sistem inventaris laboratorium dirancang untuk memantau kondisi hardware di laboratorium komputer sekolah. Setiap unit PC memiliki barcode aset, spesifikasi prosesor/RAM, dan riwayat perbaikan jika terjadi kerusakan teknis.",
                    'purpose' => 'Memastikan seluruh aset hardware lab terdata dengan baik dan mempermudah jadwal maintenance teknisi.',
                    'problem_solved' => 'Komponen periferal (mouse, keyboard, kabel LAN) sering berpindah antar meja tanpa adanya catatan riwayat penanggung jawab.',
                    'features' => "- Manajemen Aset PC & Ruang Lab\n- Riwayat Kerusakan & Jadwal Maintenance\n- Laporan Kondisi Hardware Layak / Rusak",
                    'database_structure' => [
                        'tables' => ['hardware_assets', 'maintenance_logs', 'labs', 'technicians'],
                        'relations' => [
                            'hardware_assets.lab_id -> labs.id',
                            'maintenance_logs.asset_id -> hardware_assets.id',
                        ],
                    ],
                    'sql_examples' => "SELECT a.kode_aset, a.nama_perangkat, COUNT(m.id) AS total_servis\nFROM hardware_assets a\nLEFT JOIN maintenance_logs m ON a.id = m.asset_id\nWHERE a.kondisi = 'butuh_perbaikan'\nGROUP BY a.id, a.kode_aset, a.nama_perangkat;",
                    'challenges_and_solutions' => 'Mendesain relasi yang fleksibel untuk komponen komputer yang bisa dibongkar-pasang.',
                    'lessons_learned' => 'Menerapkan indexing pada kode_aset untuk mempercepat scanning barcode.',
                    'future_improvements' => 'Integrasi cetak label QR-Code otomatis langsung dari browser.',
                    'status' => ProjectShowcase::STATUS_APPROVED,
                    'reviewed_by' => $teacher?->id,
                    'reviewed_at' => now()->subDays(1),
                    'published_at' => now()->subDays(1),
                    'likes_count' => 3,
                    'views_count' => 19,
                ],
            ];

            foreach ($showcases as $showcase) {
                $existing = ProjectShowcase::where('slug', $showcase['slug'])->first();
                if ($existing) {
                    if (in_array($showcase['slug'], ['sistem-inventaris-laboratorium-rpl', 'sistem-perpustakaan-digital'], true)) {
                        $existing->update(['thumbnail_path' => $showcase['thumbnail_path']]);
                    }

                    continue;
                }

                ProjectShowcase::create($showcase);
            }
        }
    }
}

