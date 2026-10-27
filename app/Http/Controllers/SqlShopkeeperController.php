<?php

namespace App\Http\Controllers;

use App\Models\SqlShopkeeperProgress;
use App\Services\SqlShopkeeperGame;
use App\Services\SqlShopkeeperSandbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SqlShopkeeperController extends Controller
{
    public function index(SqlShopkeeperGame $game)
    {
        $progress = $this->progress($game);
        $progress->forceFill(['last_played_at' => now()])->save();

        return view('student.leisure.sql-shopkeeper', [
            'gameState' => $progress->game_state,
            'highestLevel' => $progress->highest_level,
            'gameXp' => $progress->game_xp,
            'items' => $game::ITEMS,
            'tables' => $game->tables($progress->game_state),
            'missions' => [
                1 => ['title' => 'Rak Hampir Kosong', 'concept' => 'SELECT · WHERE · ORDER BY', 'brief' => 'Cari barang dengan stok kurang dari lima. Gunakan hasilnya untuk memilih dua produk yang perlu diisi ulang.', 'hint' => 'Petunjuk 1: bandingkan kolom stok dengan angka 5. Petunjuk 2: urutkan stok paling sedikit di atas.', 'starter' => "SELECT nama_barang, stok\nFROM barang\nWHERE stok < 5\nORDER BY stok ASC;", 'action' => 'Pilih barang di rak dan restock 8 unit.'],
                2 => ['title' => 'Favorit Pelanggan', 'concept' => 'SUM · GROUP BY · ORDER BY', 'brief' => 'Temukan barang yang paling banyak terjual dari riwayat detail penjualan.', 'hint' => 'Jumlahkan detail_penjualan.jumlah per barang, lalu urutkan total terbesar terlebih dahulu.', 'starter' => "SELECT barang.nama_barang, SUM(detail_penjualan.jumlah) AS total_terjual\nFROM detail_penjualan\nJOIN barang ON barang.id = detail_penjualan.barang_id\nGROUP BY barang.id, barang.nama_barang\nORDER BY total_terjual DESC, barang.nama_barang ASC;", 'action' => 'Jalankan query dan submit untuk membuka laporan berikutnya.'],
                3 => ['title' => 'Laporan Kasir', 'concept' => 'COUNT · SUM', 'brief' => 'Hitung jumlah transaksi dan total pemasukan toko. Transaksi pelanggan yang kamu layani ikut masuk ke laporan.', 'hint' => 'Gunakan COUNT(*) dan SUM(total) pada tabel penjualan. Beri alias jumlah_transaksi dan total_pemasukan.', 'starter' => "SELECT COUNT(*) AS jumlah_transaksi,\n       SUM(total) AS total_pemasukan\nFROM penjualan;", 'action' => 'Submit ringkasan untuk membuka daftar pemasok.'],
                4 => ['title' => 'Kontak Pemasok', 'concept' => 'JOIN · WHERE · ORDER BY', 'brief' => 'Cari barang di bawah stok minimum beserta nama pemasoknya.', 'hint' => 'Hubungkan barang.pemasok_id dengan pemasok.id, lalu bandingkan stok terhadap stok_minimum.', 'starter' => "SELECT barang.nama_barang, barang.stok, pemasok.nama_pemasok\nFROM barang\nJOIN pemasok ON pemasok.id = barang.pemasok_id\nWHERE barang.stok < barang.stok_minimum\nORDER BY barang.stok ASC, barang.nama_barang ASC;", 'action' => 'Submit hasil JOIN untuk membuka keputusan bisnis.'],
                5 => ['title' => 'Prioritas Pemesanan', 'concept' => 'JOIN · Ekspresi · ORDER BY', 'brief' => 'Susun prioritas restock berdasarkan selisih stok terhadap batas minimum. Terapkan rekomendasi teratas untuk menyelesaikan simulasi.', 'hint' => 'Buat kolom prioritas dari stok_minimum - stok. Prioritas terbesar harus tampil lebih dulu.', 'starter' => "SELECT barang.nama_barang, barang.stok, barang.stok_minimum,\n       barang.stok_minimum - barang.stok AS prioritas,\n       pemasok.nama_pemasok\nFROM barang\nJOIN pemasok ON pemasok.id = barang.pemasok_id\nWHERE barang.stok < barang.stok_minimum\nORDER BY prioritas DESC, barang.nama_barang ASC;", 'action' => 'Pilih barang prioritas tertinggi di area gudang lalu pesan stok.'],
            ],
            'queryUrl' => route('siswa.fun-zone.sql-shopkeeper.query'),
            'actionUrl' => route('siswa.fun-zone.sql-shopkeeper.action'),
            'catalogUrl' => route('siswa.leisure.activity', ['activity' => 'mini-games-sql']),
        ]);
    }

    public function query(Request $request, SqlShopkeeperGame $game, SqlShopkeeperSandbox $sandbox): JsonResponse
    {
        $data = $request->validate([
            'query' => 'required|string|max:4000',
            'level' => 'required|integer|min:1|max:5',
            'submit' => 'sometimes|boolean',
        ]);

        return DB::transaction(function () use ($request, $game, $sandbox, $data) {
            $progress = $this->progress($game, true);
            $state = $progress->game_state;
            $level = (int) $data['level'];
            abort_if($level > $progress->highest_level, 403, 'Selesaikan misi sebelumnya dahulu.');
            try {
                $result = $sandbox->run($data['query'], $state);
            } catch (\InvalidArgumentException $error) {
                return response()->json(['ok' => false, 'message' => $error->getMessage()], 422);
            }

            $state['last_query'] = ['level' => $level, 'query' => $data['query'], 'rows' => $result['rows'], 'columns' => $result['columns']];
            $complete = false;
            $message = 'Query berhasil dijalankan. Baca hasilnya lalu gunakan untuk mengambil keputusan di toko.';
            if ($request->boolean('submit')) {
                if (! $game->queryCorrect($level, $result, $state)) {
                    $message = $this->feedbackFor($level);
                } else {
                    $state['query_passed'][(string) $level] = true;
                    $message = $level === 1 || $level === 5
                        ? 'Hasil query cocok. Sekarang lakukan keputusan stok yang direkomendasikan untuk menuntaskan misi.'
                        : 'Misi query selesai! Temuanmu sudah diverifikasi oleh data toko.';
                    if (in_array($level, [2, 3, 4], true)) {
                        $complete = $this->completeMission($progress, $state, $level);
                    }
                }
            }

            $progress->game_state = $state;
            $progress->last_played_at = now();
            $progress->save();

            return response()->json([
                'ok' => true,
                'columns' => $result['columns'],
                'rows' => $result['rows'],
                'elapsed' => $result['ms'] ?? 0,
                'message' => $message,
                'success' => (bool) $request->boolean('submit') && $game->queryCorrect($level, $result, $state),
                'missionComplete' => $complete,
                'progress' => $this->publicState($progress),
            ]);
        });
    }

    public function action(Request $request, SqlShopkeeperGame $game): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:restock,serve_customer',
            'item_id' => 'nullable|integer|min:1|max:5',
        ]);

        return DB::transaction(function () use ($game, $data) {
            $progress = $this->progress($game, true);
            $state = $progress->game_state;
            $message = '';
            $success = false;
            $missionComplete = false;

            if ($data['action'] === 'restock') {
                $itemId = (int) ($data['item_id'] ?? 0);
                abort_unless(isset($game::ITEMS[$itemId]), 422, 'Pilih barang yang tersedia.');
                $level = $progress->highest_level;
                abort_unless(in_array($level, [1, 5], true) && ! empty($state['query_passed'][(string) $level]), 422, 'Jalankan dan submit query misi dahulu.');
                abort_if($level === 5 && in_array(5, $state['completed_levels'], true), 422, 'Misi prioritas sudah selesai.');

                if ($level === 1) {
                    $stock = (int) $state['inventory'][$itemId];
                    abort_if($stock >= 5, 422, 'Barang ini tidak termasuk stok rendah.');
                    abort_if(in_array($itemId, $state['restocked_items'], true), 422, 'Barang ini sudah di-restock untuk misi ini.');
                } else {
                    $priority = $game->expected(5, $state)[0]['nama_barang'] ?? null;
                    abort_unless(($game::ITEMS[$itemId]['nama_barang'] ?? null) === $priority, 422, 'Pilih barang dengan prioritas tertinggi dari hasil query.');
                }

                $packSize = 8;
                $cost = $game::ITEMS[$itemId]['biaya'] * $packSize;
                abort_if($state['balance'] < $cost, 422, 'Saldo toko tidak cukup untuk memesan satu paket.');
                $state['balance'] -= $cost;
                $state['inventory'][$itemId] += $packSize;
                if ($level === 1) {
                    $state['restocked_items'][] = $itemId;
                    $success = true;
                    $message = 'Stok '.$game::ITEMS[$itemId]['nama_barang'].' bertambah 8. Saldo toko diperbarui.';
                    if (count($state['restocked_items']) >= 2) {
                        $missionComplete = $this->completeMission($progress, $state, 1);
                        $message = 'Misi stok selesai! Dua barang berhasil di-restock dan Level 2 terbuka.';
                    }
                } else {
                    $missionComplete = $this->completeMission($progress, $state, 5);
                    $success = true;
                    $message = 'Pesanan prioritas diterapkan. Semua misi utama toko selesai!';
                }
            } else {
                abort_if($progress->highest_level < 2, 403, 'Selesaikan misi stok untuk mulai melayani pelanggan.');
                $customer = $state['customer_queue'][0] ?? null;
                abort_if(! $customer, 422, 'Antrean pelanggan hari ini sudah selesai.');
                $itemId = $customer['item_id'];
                if (($state['inventory'][$itemId] ?? 0) < $customer['qty']) {
                    $state['reputation'] = max(0, $state['reputation'] - 2);
                    $message = $customer['name'].' belum bisa dilayani karena '.$game::ITEMS[$itemId]['nama_barang'].' habis. Restock barang itu lebih dulu.';
                } else {
                    array_shift($state['customer_queue']);
                    $state['inventory'][$itemId] -= $customer['qty'];
                    $state['balance'] += $game::ITEMS[$itemId]['harga'] * $customer['qty'];
                    $customerIds = ['Naya' => 1, 'Bimo' => 2, 'Salsa' => 3, 'Rafi' => 4, 'Tari' => 5];
                    $state['sales'][] = ['customer_id' => $customerIds[$customer['name']] ?? 1, 'items' => [['item_id' => $itemId, 'qty' => $customer['qty']]]];
                    $state['served_count']++;
                    $state['reputation'] = min(100, $state['reputation'] + 2);
                    $progress->game_xp += 10;
                    $success = true;
                    $message = 'Pesanan '.$customer['name'].' berhasil diproses. Stok dan laporan penjualan diperbarui.';
                    if ($state['served_count'] >= 5 && empty($state['rewards']['served_five'])) {
                        $state['rewards']['served_five'] = true;
                        $progress->game_xp += 50;
                        $message .= ' Target harian tercapai: 5 pelanggan dilayani (+50 XP game).';
                    }
                }
            }

            $progress->game_state = $state;
            $progress->last_played_at = now();
            $progress->save();

            return response()->json([
                'ok' => true,
                'success' => $success,
                'missionComplete' => $missionComplete,
                'message' => $message,
                'progress' => $this->publicState($progress),
            ]);
        });
    }

    private function progress(SqlShopkeeperGame $game, bool $lock = false): SqlShopkeeperProgress
    {
        $query = SqlShopkeeperProgress::query()->where('student_id', auth()->id());
        if ($lock) {
            $query->lockForUpdate();
        }
        $record = $query->first();

        return $record ?: SqlShopkeeperProgress::create([
            'student_id' => auth()->id(),
            'game_state' => $game->initialState(),
            'highest_level' => 1,
            'game_xp' => 0,
        ]);
    }

    private function completeMission(SqlShopkeeperProgress $progress, array &$state, int $level): bool
    {
        if (! in_array($level, $state['completed_levels'], true)) {
            $state['completed_levels'][] = $level;
            if (empty($state['rewards']['level_'.$level])) {
                $state['rewards']['level_'.$level] = true;
                $progress->game_xp += 100;
            }
        }
        $progress->highest_level = max($progress->highest_level, min(5, $level + 1));

        return true;
    }

    private function feedbackFor(int $level): string
    {
        return match ($level) {
            1 => 'Filter stok belum tepat. Tampilkan semua barang dengan stok di bawah 5, urutkan dari stok paling sedikit.',
            2 => 'Periksa SUM(jumlah), GROUP BY nama_barang, lalu urutkan total terjual dari terbesar.',
            3 => 'Ringkasan perlu memuat COUNT transaksi dan SUM total pemasukan dengan alias yang diminta.',
            4 => 'Hubungkan barang dengan pemasok, filter stok di bawah minimum, lalu urutkan dari stok terendah.',
            5 => 'Tampilkan selisih stok terhadap minimum sebagai prioritas dan urutkan prioritas terbesar dahulu.',
        };
    }

    private function publicState(SqlShopkeeperProgress $progress): array
    {
        $game = app(SqlShopkeeperGame::class);
        $state = $progress->game_state;

        return [
            'inventory' => $state['inventory'],
            'balance' => $state['balance'],
            'reputation' => $state['reputation'],
            'servedCount' => $state['served_count'],
            'waiting' => $state['customer_queue'][0] ?? null,
            'queueCount' => count($state['customer_queue']),
            'highestLevel' => $progress->highest_level,
            'completedLevels' => $state['completed_levels'],
            'restockedItems' => $state['restocked_items'],
            'queryPassed' => $state['query_passed'],
            'xp' => $progress->game_xp,
            'tables' => $game->tables($state),
        ];
    }
}
