<?php

namespace App\Http\Controllers;

use App\Services\Progress;
use App\Services\SqlSandbox;
use App\Models\ProjectShowcase;
use App\Models\SqlShopkeeperProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LeisureController extends Controller
{
    private const ACTIVITIES = [
        'tebak-query' => ['title' => 'Tebak Hasil Query', 'description' => 'Uji logikamu dengan menebak hasil dari perintah SQL.', 'action' => 'Mainkan', 'icon' => 'query', 'theme' => 'blue'],
        'query-detective' => ['title' => 'Query Detective', 'description' => 'Temukan kesalahan pada query dan perbaiki agar menghasilkan data yang benar.', 'action' => 'Mainkan', 'icon' => 'detective', 'theme' => 'purple'],
        'database-fun-facts' => ['title' => 'Database Fun Facts', 'description' => 'Jelajahi fakta menarik seputar database dan teknologi di dunia nyata.', 'action' => 'Jelajahi', 'icon' => 'facts', 'theme' => 'teal'],
        'database-escape-room' => ['title' => 'Database Escape Room', 'description' => 'Pecahkan teka-teki SQL untuk membuka jalan keluar dari ruang server.', 'action' => 'Mainkan', 'icon' => 'escape', 'theme' => 'blue'],
        'ruang-diskusi' => ['title' => 'Ruang Diskusi', 'description' => 'Bertanya, berbagi solusi SQL, dan berdiskusi bersama teman.', 'action' => 'Buka Diskusi', 'icon' => 'discussion', 'theme' => 'indigo'],
        'pameran-karya' => ['title' => 'Pameran Karya', 'description' => 'Lihat dan dapatkan inspirasi dari project basis data karya teman-teman.', 'action' => 'Jelajahi', 'icon' => 'gallery', 'theme' => 'violet'],
        'mini-games-sql' => ['title' => 'Mini Games SQL', 'description' => 'Bermain game seru untuk mengasah pemahaman SQL-mu.', 'action' => 'Mainkan', 'icon' => 'game', 'theme' => 'blue'],
    ];

    public function index(Progress $progress)
    {
        $stats = $progress->forStudent(auth()->user());

        return view('student.leisure.index', [
            'activities' => array_diff_key(self::ACTIVITIES, ['database-escape-room' => true]),
            'xp' => $stats['xp'],
            'badgeCount' => $stats['whereBadge'] ? 1 : 0,
            'publishedShowcaseCount' => ProjectShowcase::approved()->count(),
        ]);
    }

    public function show(string $activity)
    {
        if ($activity === 'ruang-diskusi') {
            return redirect()->route('siswa.fun-zone.discussions');
        }
        if ($activity === 'pameran-karya') {
            return redirect()->route('siswa.fun-zone.showcase');
        }

        abort_unless(isset(self::ACTIVITIES[$activity]), 404);

        $details = match ($activity) {
            'tebak-query' => [
                'query' => "SELECT nama FROM siswa\nWHERE kelas = 'XI RPL 1' AND nilai > 85;",
                'question' => 'Siapa nama siswa yang muncul dari query ini?',
                'options' => ['a' => 'Andi dan Rina', 'b' => 'Rina', 'c' => 'Rina dan Dewi'],
            ],
            'query-detective' => [
                'query' => 'SELECT nama FROM pelanggan WHERE kota = Surabaya;',
                'question' => 'Apa perbaikan yang tepat agar query mencari pelanggan dari Surabaya?',
                'options' => ['a' => "WHERE kota = 'Surabaya'", 'b' => 'WHERE kota = Bandung', 'c' => 'WHERE kota LIKE Surabaya'],
            ],
            'database-fun-facts' => ['facts' => [
                ['title' => 'Musik dan playlist', 'body' => 'Layanan musik menghubungkan pengguna, lagu, album, dan playlist. Relasi ini membantu aplikasi menyusun koleksi dan rekomendasi.'],
                ['title' => 'Toko online', 'body' => 'Katalog, stok, pesanan, pembayaran, dan pengiriman disimpan sebagai data yang saling berhubungan. Index membantu pencarian produk tetap cepat.'],
                ['title' => 'Game online', 'body' => 'Profil, inventaris, dan progres pemain dapat disimpan agar dapat dilanjutkan saat pemain kembali.'],
                ['title' => 'Sekolah', 'body' => 'Data kelas, siswa, mata pelajaran, dan nilai dapat dihubungkan agar laporan lebih konsisten dan mudah ditelusuri.'],
            ]],
            'database-escape-room' => [
                'mission' => 'Terminal ruang server terkunci. Temukan nama pelanggan yang berasal dari Surabaya untuk membuka pintu.',
                'query' => "SELECT nama FROM pelanggan WHERE kota = 'Surabaya';",
                'hint' => 'Gunakan tabel pelanggan. Kolom kota berisi nama kota; tulis nilai teks di antara tanda petik.',
            ],
            'pameran-karya' => [],
            'mini-games-sql' => ['games' => ['sql-shopkeeper']],
            default => [],
        };

        $shopkeeperProgress = $activity === 'mini-games-sql' && Schema::hasTable('sql_shopkeeper_progress')
            ? SqlShopkeeperProgress::where('student_id', auth()->id())->first()
            : null;

        return view('student.leisure.activity', [
            'slug' => $activity,
            'activity' => self::ACTIVITIES[$activity],
            'details' => $details,
            'shopkeeperProgress' => $shopkeeperProgress,
        ]);
    }

    public function submit(Request $request, string $activity, SqlSandbox $sandbox)
    {
        abort_unless(isset(self::ACTIVITIES[$activity]), 404);

        if ($activity === 'tebak-query' || $activity === 'query-detective') {
            abort(404);
        }
        abort_unless($activity === 'database-escape-room', 404);
        $query = $request->validate(['query' => 'required|string|max:4000'])['query'];

        try {
            $result = $sandbox->run($query);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('feedback', $e->getMessage());
        }

        $expected = [['nama' => 'Nina'], ['nama' => 'Dimas']];
        if ($sandbox->equivalent($result['rows'], $expected)) {
            return back()->with('success', 'Pintu terbuka! Kamu menemukan Nina dan Dimas dari Surabaya.');
        }

        return back()->withInput()->with('feedback', 'Query berhasil dijalankan, tetapi hasilnya belum cocok. Periksa kondisi kota dan kolom yang dipilih.');
    }
}
