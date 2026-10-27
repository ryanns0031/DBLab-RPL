<?php

namespace App\Services;

class QueryGuessGame
{
    public const POINTS_PER_ANSWER = 10;

    public function questions(): array
    {
        $students = [
            ['id' => 1, 'nama' => 'Andi', 'kelas' => 'X RPL', 'nilai' => 85],
            ['id' => 2, 'nama' => 'Budi', 'kelas' => 'X RPL', 'nilai' => 90],
            ['id' => 3, 'nama' => 'Citra', 'kelas' => 'X MM', 'nilai' => 78],
            ['id' => 4, 'nama' => 'Dimas', 'kelas' => 'X RPL', 'nilai' => 88],
            ['id' => 5, 'nama' => 'Eka', 'kelas' => 'X MM', 'nilai' => 92],
        ];

        return [
            'strict-where' => $this->makeQuestion('WHERE dan >', 'Mudah', $students, ['id', 'nama', 'kelas', 'nilai'], "SELECT nama, nilai\nFROM siswa\nWHERE kelas = 'X RPL'\n  AND nilai > 85;", 'Apa hasil dari query di atas?', [
                'A' => [['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Dimas', 'nilai' => 88]],
                'B' => [['nama' => 'Andi', 'nilai' => 85], ['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Dimas', 'nilai' => 88]],
                'C' => [['nama' => 'Eka', 'nilai' => 92]],
                'D' => [['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Eka', 'nilai' => 92]],
            ], 'A', 'WHERE kelas membatasi hasil ke X RPL. Operator > tidak memasukkan Andi karena nilainya tepat 85.', ['Kolom yang dipilih adalah nama dan nilai.', 'Periksa kelas pada setiap baris.', 'Nilai harus lebih besar dari 85, bukan sama dengan 85.']),
            'less-than' => $this->makeQuestion('WHERE dan <', 'Mudah', $students, ['id', 'nama', 'kelas', 'nilai'], "SELECT nama\nFROM siswa\nWHERE nilai < 85;", 'Nama siapa yang muncul?', [
                'A' => [['nama' => 'Andi'], ['nama' => 'Citra']],
                'B' => [['nama' => 'Budi'], ['nama' => 'Dimas']],
                'C' => [['nama' => 'Citra']],
                'D' => [['nama' => 'Citra'], ['nama' => 'Eka']],
            ], 'C', 'Operator < berarti nilainya harus kurang dari 85. Hanya Citra yang memenuhi kondisi.', ['Cari nilai yang lebih kecil dari 85.', 'Nilai 85 tidak termasuk karena operatornya <.', 'Perhatikan nilai Citra dan baris siswa lainnya.']),
            'and-where' => $this->makeQuestion('AND', 'Mudah', $students, ['id', 'nama', 'kelas', 'nilai'], "SELECT nama, nilai\nFROM siswa\nWHERE kelas = 'X RPL'\n  AND nilai >= 85;", 'Baris mana yang memenuhi kedua kondisi?', [
                'A' => [['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Dimas', 'nilai' => 88]],
                'B' => [['nama' => 'Andi', 'nilai' => 85], ['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Dimas', 'nilai' => 88]],
                'C' => [['nama' => 'Andi', 'nilai' => 85], ['nama' => 'Citra', 'nilai' => 78], ['nama' => 'Dimas', 'nilai' => 88]],
                'D' => [['nama' => 'Andi', 'nilai' => 85], ['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Dimas', 'nilai' => 88], ['nama' => 'Eka', 'nilai' => 92]],
            ], 'B', 'AND mengharuskan kelas X RPL dan nilai minimal 85 terpenuhi sekaligus. Nilai 85 termasuk karena operatornya >=.', ['AND berarti kedua syarat harus terpenuhi.', 'Saring kelas X RPL terlebih dahulu.', '>= 85 memasukkan nilai yang sama dengan 85.']),
            'or-where' => $this->makeQuestion('OR', 'Menengah', $students, ['id', 'nama', 'kelas', 'nilai'], "SELECT nama, nilai\nFROM siswa\nWHERE kelas = 'X MM'\n   OR nilai >= 90\nORDER BY nama;", 'Baris mana yang memenuhi salah satu kondisi?', [
                'A' => [['nama' => 'Citra', 'nilai' => 78], ['nama' => 'Eka', 'nilai' => 92]],
                'B' => [['nama' => 'Andi', 'nilai' => 85], ['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Eka', 'nilai' => 92]],
                'C' => [['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Citra', 'nilai' => 78]],
                'D' => [['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Citra', 'nilai' => 78], ['nama' => 'Eka', 'nilai' => 92]],
            ], 'D', 'OR menerima baris yang kelasnya X MM atau nilainya minimal 90. Budi memenuhi syarat nilai, sedangkan Citra dan Eka memenuhi syarat kelas.', ['OR cukup memenuhi salah satu kondisi.', 'Semua siswa X MM masuk.', 'Tambahkan siswa dari kelas lain yang nilainya minimal 90.']),
            'order-limit' => $this->makeQuestion('ORDER BY dan LIMIT', 'Menengah', $students, ['id', 'nama', 'kelas', 'nilai'], "SELECT nama, nilai\nFROM siswa\nORDER BY nilai DESC\nLIMIT 3;", 'Tiga nilai tertinggi muncul dengan urutan apa?', [
                'A' => [['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Eka', 'nilai' => 92], ['nama' => 'Dimas', 'nilai' => 88]],
                'B' => [['nama' => 'Andi', 'nilai' => 85], ['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Dimas', 'nilai' => 88]],
                'C' => [['nama' => 'Eka', 'nilai' => 92], ['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Dimas', 'nilai' => 88]],
                'D' => [['nama' => 'Eka', 'nilai' => 92], ['nama' => 'Dimas', 'nilai' => 88], ['nama' => 'Budi', 'nilai' => 90]],
            ], 'C', 'DESC mengurutkan dari nilai terbesar ke terkecil. LIMIT 3 mengambil tiga baris teratas setelah pengurutan.', ['DESC berarti urutan menurun.', 'Urutkan nilai dari yang paling besar.', 'Ambil tiga baris pertama setelah diurutkan.']),
            'count-filter' => $this->makeQuestion('COUNT dan WHERE', 'Mudah', $students, ['id', 'nama', 'kelas', 'nilai'], "SELECT COUNT(*) AS jumlah\nFROM siswa\nWHERE kelas = 'X RPL';", 'Berapa jumlah siswa kelas X RPL?', [
                'A' => [['jumlah' => 3]],
                'B' => [['jumlah' => 2]],
                'C' => [['jumlah' => 4]],
                'D' => [['jumlah' => 5]],
            ], 'A', 'COUNT(*) menghitung baris yang lolos WHERE. Ada tiga siswa dengan kelas X RPL.', ['COUNT(*) menghitung jumlah baris.', 'Abaikan siswa dari X MM.', 'Hitung baris Andi, Budi, dan Dimas.']),
            'average' => $this->makeQuestion('AVG dan ROUND', 'Menengah', $students, ['id', 'nama', 'kelas', 'nilai'], "SELECT ROUND(AVG(nilai), 2) AS rata_rata\nFROM siswa\nWHERE kelas = 'X RPL';", 'Berapa rata-rata nilai kelas X RPL setelah dibulatkan dua desimal?', [
                'A' => [['rata_rata' => 88]],
                'B' => [['rata_rata' => 86.5]],
                'C' => [['rata_rata' => 87]],
                'D' => [['rata_rata' => 87.67]],
            ], 'D', 'AVG menghitung (85 + 90 + 88) / 3 = 87,666…. ROUND dengan dua angka desimal menghasilkan 87,67.', ['AVG menghitung nilai rata-rata.', 'Gunakan hanya tiga baris kelas X RPL.', 'Bulatkan hasil ke dua angka di belakang koma.']),
            'group-count' => $this->makeQuestion('GROUP BY dan COUNT', 'Menengah', $students, ['id', 'nama', 'kelas', 'nilai'], "SELECT kelas, COUNT(*) AS jumlah\nFROM siswa\nGROUP BY kelas\nORDER BY kelas;", 'Berapa jumlah siswa pada setiap kelas?', [
                'A' => [['kelas' => 'X MM', 'jumlah' => 3], ['kelas' => 'X RPL', 'jumlah' => 2]],
                'B' => [['kelas' => 'X MM', 'jumlah' => 2], ['kelas' => 'X RPL', 'jumlah' => 3]],
                'C' => [['kelas' => 'X RPL', 'jumlah' => 3]],
                'D' => [['kelas' => 'X MM', 'jumlah' => 2], ['kelas' => 'X RPL', 'jumlah' => 2]],
            ], 'B', 'GROUP BY membentuk satu kelompok untuk setiap kelas. X MM memiliki dua siswa dan X RPL memiliki tiga siswa.', ['GROUP BY membuat kelompok untuk tiap nilai kelas.', 'COUNT(*) menghitung baris pada kelompok.', 'Pastikan hasil menampilkan kedua kelas.']),
            'sum-join' => $this->makeQuestion('JOIN dan SUM', 'Sulit', $this->customers(), ['pelanggan' => ['id', 'nama'], 'pesanan' => ['id', 'pelanggan_id', 'total']], "SELECT pelanggan.nama, SUM(pesanan.total) AS total_belanja\nFROM pelanggan\nJOIN pesanan ON pesanan.pelanggan_id = pelanggan.id\nGROUP BY pelanggan.id, pelanggan.nama\nORDER BY pelanggan.nama;", 'Berapa total belanja setiap pelanggan?', [
                'A' => [['nama' => 'Dimas', 'total_belanja' => 200000], ['nama' => 'Nina', 'total_belanja' => 225000]],
                'B' => [['nama' => 'Dimas', 'total_belanja' => 200000], ['nama' => 'Nina', 'total_belanja' => 150000]],
                'C' => [['nama' => 'Nina', 'total_belanja' => 225000]],
                'D' => [['nama' => 'Dimas', 'total_belanja' => 275000], ['nama' => 'Nina', 'total_belanja' => 150000]],
            ], 'A', 'JOIN memasangkan setiap pesanan dengan pelanggannya. SUM menjumlahkan dua pesanan Nina (150.000 + 75.000) dan satu pesanan Dimas (200.000).', ['JOIN memakai pelanggan_id untuk memasangkan baris.', 'SUM menjumlahkan total pesanan dalam tiap kelompok.', 'Nina mempunyai dua pesanan; Dimas mempunyai satu.']),
            'join-filter-order' => $this->makeQuestion('JOIN, WHERE, dan ORDER BY', 'Sulit', $this->customers(), ['pelanggan' => ['id', 'nama'], 'pesanan' => ['id', 'pelanggan_id', 'total']], "SELECT pelanggan.nama, pesanan.total\nFROM pelanggan\nJOIN pesanan ON pesanan.pelanggan_id = pelanggan.id\nWHERE pesanan.total >= 150000\nORDER BY pesanan.total DESC;", 'Pesanan mana yang bernilai minimal 150.000, dari terbesar ke terkecil?', [
                'A' => [['nama' => 'Nina', 'total' => 150000], ['nama' => 'Dimas', 'total' => 200000]],
                'B' => [['nama' => 'Nina', 'total' => 150000], ['nama' => 'Nina', 'total' => 75000]],
                'C' => [['nama' => 'Dimas', 'total' => 200000], ['nama' => 'Nina', 'total' => 150000]],
                'D' => [['nama' => 'Dimas', 'total' => 200000], ['nama' => 'Nina', 'total' => 150000], ['nama' => 'Nina', 'total' => 75000]],
            ], 'C', 'WHERE membuang pesanan Nina sebesar 75.000. JOIN menampilkan nama pelanggan dan ORDER BY DESC mengurutkan Dimas 200.000 sebelum Nina 150.000.', ['JOIN menghubungkan pesanan dengan nama pelanggan.', '>= 150000 tidak memasukkan pesanan 75.000.', 'DESC menempatkan 200.000 sebelum 150.000.']),
        ];
    }

    public function question(string $id): ?array
    {
        return $this->questions()[$id] ?? null;
    }

    private function makeQuestion(string $topic, string $difficulty, array $dataset, array $columns, string $query, string $prompt, array $options, string $answer, string $explanation, array $hints): array
    {
        return compact('topic', 'difficulty', 'dataset', 'columns', 'query', 'prompt', 'options', 'answer', 'explanation', 'hints');
    }

    private function customers(): array
    {
        return [
            'pelanggan' => [
                ['id' => 1, 'nama' => 'Nina'],
                ['id' => 2, 'nama' => 'Dimas'],
            ],
            'pesanan' => [
                ['id' => 1, 'pelanggan_id' => 1, 'total' => 150000],
                ['id' => 2, 'pelanggan_id' => 2, 'total' => 200000],
                ['id' => 3, 'pelanggan_id' => 1, 'total' => 75000],
            ],
        ];
    }
}
