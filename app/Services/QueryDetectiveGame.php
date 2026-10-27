<?php

namespace App\Services;

class QueryDetectiveGame
{
    public const POINTS_PER_CASE = 10;

    public function cases(): array
    {
        $students = [
            ['id' => 1, 'nama' => 'Andi', 'kelas' => 'X RPL', 'nilai' => 85],
            ['id' => 2, 'nama' => 'Budi', 'kelas' => 'X RPL', 'nilai' => 90],
            ['id' => 3, 'nama' => 'Citra', 'kelas' => 'X MM', 'nilai' => 78],
            ['id' => 4, 'nama' => 'Dimas', 'kelas' => 'X RPL', 'nilai' => 88],
            ['id' => 5, 'nama' => 'Eka', 'kelas' => 'X MM', 'nilai' => 92],
        ];
        $orders = [
            'pelanggan' => [['id' => 1, 'nama' => 'Nina'], ['id' => 2, 'nama' => 'Dimas']],
            'pesanan' => [['id' => 1, 'pelanggan_id' => 1, 'total' => 150000], ['id' => 2, 'pelanggan_id' => 2, 'total' => 200000], ['id' => 3, 'pelanggan_id' => 1, 'total' => 75000]],
        ];

        $cases = [
            'syntax-keywords' => $this->makeCase('Sintaks dan pengurutan', 'Data Siswa Berprestasi', 'Tim OSIS ingin menampilkan nama dan nilai siswa kelas X RPL yang nilainya lebih dari 85, diurutkan dari nilai tertinggi.', 'Mudah', 'Sintaks SQL', ['siswa' => $students], "SELECT nama, nilai\nFROM siswa\nWHRRE kelas = 'X RPL'\nAND nilai > 85\nORDERBY nilai DESC;", ['WHRRE', 'ORDERBY'], "SELECT nama, nilai\nFROM siswa\nWHERE kelas = 'X RPL'\n  AND nilai > 85\nORDER BY nilai DESC;", 'WHRRE seharusnya WHERE. ORDERBY harus ditulis sebagai dua keyword: ORDER BY.', [['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Dimas', 'nilai' => 88]], ['SELECT dan FROM sudah benar.', 'Periksa keyword untuk menyaring data.', 'Keyword pengurutan terdiri dari dua kata.', 'Pastikan setiap klausa SQL dipisahkan dengan spasi.']),
            'comparison-operator' => $this->makeCase('Operator perbandingan', 'Nilai di Atas Batas', 'Guru hanya ingin melihat siswa dengan nilai lebih dari 85. Nilai yang sama dengan 85 tidak boleh ikut.', 'Mudah', 'WHERE dan operator', ['siswa' => $students], "SELECT nama, nilai\nFROM siswa\nWHERE nilai >= 85;", ['>='], "SELECT nama, nilai\nFROM siswa\nWHERE nilai > 85;", 'Operator >= juga memasukkan nilai 85. Kebutuhan kasus meminta nilai yang lebih besar saja, jadi gunakan >.', [['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Dimas', 'nilai' => 88], ['nama' => 'Eka', 'nilai' => 92]], ['Perhatikan arti “lebih dari”.', 'Operator >= mencakup nilai yang sama dengan batas.', 'Pilih operator yang tidak memasukkan nilai 85.']),
            'column-name' => $this->makeCase('Nama kolom', 'Daftar Nama Siswa', 'Tampilkan nama semua siswa kelas X RPL. Query harus memakai nama kolom yang tersedia pada tabel.', 'Mudah', 'SELECT dan kolom', ['siswa' => $students], "SELECT nam\nFROM siswa\nWHERE kelas = 'X RPL';", ['nam'], "SELECT nama\nFROM siswa\nWHERE kelas = 'X RPL';", 'Kolom pada tabel bernama nama. nam tidak dikenal sebagai kolom.', [['nama' => 'Andi'], ['nama' => 'Budi'], ['nama' => 'Dimas']], ['Lihat kembali nama kolom pada tabel.', 'SELECT menyebutkan kolom yang akan ditampilkan.', 'Periksa ejaan nama kolom.']),
            'where-value' => $this->makeCase('Kondisi WHERE', 'Siswa Kelas X RPL', 'Wali kelas ingin melihat nama siswa yang berasal dari X RPL saja.', 'Mudah', 'WHERE', ['siswa' => $students], "SELECT nama, kelas\nFROM siswa\nWHERE kelas = 'X MM';", ["'X MM'"], "SELECT nama, kelas\nFROM siswa\nWHERE kelas = 'X RPL';", 'Query membandingkan kelas dengan X MM. Ganti nilai kondisi agar sesuai permintaan, yaitu X RPL.', [['nama' => 'Andi', 'kelas' => 'X RPL'], ['nama' => 'Budi', 'kelas' => 'X RPL'], ['nama' => 'Dimas', 'kelas' => 'X RPL']], ['Periksa teks di sebelah kanan tanda sama dengan.', 'Pastikan nilai filter sesuai kelas yang diminta.', 'Dataset memiliki X RPL dan X MM.']),
            'and-or' => $this->makeCase('Logika AND dan OR', 'Siswa Berprestasi dari X RPL', 'Tampilkan siswa X RPL yang nilainya lebih dari 85. Kedua kondisi harus terpenuhi.', 'Menengah', 'AND dan OR', ['siswa' => $students], "SELECT nama, nilai\nFROM siswa\nWHERE kelas = 'X RPL'\n   OR nilai > 85;", ['OR'], "SELECT nama, nilai\nFROM siswa\nWHERE kelas = 'X RPL'\n  AND nilai > 85;", 'OR cukup memenuhi salah satu kondisi sehingga siswa dari kelas lain dapat ikut. AND mewajibkan kelas dan nilai sama-sama sesuai.', [['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Dimas', 'nilai' => 88]], ['Kasus meminta kelas dan nilai sekaligus.', 'OR berarti salah satu kondisi cukup.', 'Gunakan operator yang mengharuskan kedua kondisi benar.']),
            'sort-order' => $this->makeCase('ORDER BY', 'Peringkat Nilai', 'Urutkan seluruh siswa dari nilai tertinggi ke terendah.', 'Mudah', 'ORDER BY', ['siswa' => $students], "SELECT nama, nilai\nFROM siswa\nORDER BY nilai ASC;", ['ASC'], "SELECT nama, nilai\nFROM siswa\nORDER BY nilai DESC;", 'ASC mengurutkan dari nilai terkecil. Untuk nilai tertinggi lebih dulu, gunakan DESC.', [['nama' => 'Eka', 'nilai' => 92], ['nama' => 'Budi', 'nilai' => 90], ['nama' => 'Dimas', 'nilai' => 88], ['nama' => 'Andi', 'nilai' => 85], ['nama' => 'Citra', 'nilai' => 78]], ['ASC berarti menaik.', 'Nilai tertinggi harus muncul pada baris pertama.', 'Arah pengurutan menurun ditulis dengan keyword DESC.']),
            'group-column' => $this->makeCase('GROUP BY', 'Jumlah Siswa per Kelas', 'Hitung jumlah siswa pada setiap kelas.', 'Menengah', 'GROUP BY dan COUNT', ['siswa' => $students], "SELECT kelas, COUNT(*) AS jumlah\nFROM siswa\nGROUP BY nama;", ['nama'], "SELECT kelas, COUNT(*) AS jumlah\nFROM siswa\nGROUP BY kelas;", 'Data harus dikelompokkan berdasarkan kelas, bukan nama siswa.', [['kelas' => 'X MM', 'jumlah' => 2], ['kelas' => 'X RPL', 'jumlah' => 3]], ['COUNT menghitung baris dalam setiap kelompok.', 'Satu hasil diharapkan untuk setiap kelas.', 'Kelompokkan dengan kolom yang diminta pada laporan.']),
            'having-average' => $this->makeCase('Agregasi dan HAVING', 'Rata-rata Kelas', 'Tampilkan kelas dengan rata-rata nilai di atas 85.', 'Sulit', 'AVG, GROUP BY, HAVING', ['siswa' => $students], "SELECT kelas, AVG(nilai) AS rata_rata\nFROM siswa\nWHERE AVG(nilai) > 85\nGROUP BY kelas;", ['WHERE'], "SELECT kelas, ROUND(AVG(nilai), 2) AS rata_rata\nFROM siswa\nGROUP BY kelas\nHAVING AVG(nilai) > 85;", 'WHERE menyaring baris sebelum agregasi. Kondisi pada hasil kelompok harus memakai HAVING setelah GROUP BY.', [['kelas' => 'X RPL', 'rata_rata' => 87.67]], ['AVG adalah fungsi agregat.', 'WHERE berjalan sebelum GROUP BY.', 'Filter nilai agregat menggunakan HAVING setelah GROUP BY.']),
            'join-key' => $this->makeCase('Relasi JOIN', 'Pesanan dan Pelanggan', 'Tampilkan nama pelanggan dan total setiap pesanannya.', 'Sulit', 'JOIN dan foreign key', $orders, "SELECT pelanggan.nama, pesanan.total\nFROM pelanggan\nJOIN pesanan ON pesanan.id = pelanggan.id\nORDER BY pesanan.id;", ['pesanan.id'], "SELECT pelanggan.nama, pesanan.total\nFROM pelanggan\nJOIN pesanan ON pesanan.pelanggan_id = pelanggan.id\nORDER BY pesanan.id;", 'pesanan.id adalah identitas pesanan, bukan relasi ke pelanggan. Hubungkan pelanggan_id pada pesanan dengan id pelanggan.', [['nama' => 'Nina', 'total' => 150000], ['nama' => 'Dimas', 'total' => 200000], ['nama' => 'Nina', 'total' => 75000]], ['Cari kolom foreign key pada tabel pesanan.', 'JOIN mencocokkan id pelanggan.', 'Gunakan pesanan.pelanggan_id untuk memasangkan record.']),
            'sum-orders' => $this->makeCase('SUM dan GROUP BY', 'Total Belanja Pelanggan', 'Hitung total seluruh pesanan untuk setiap pelanggan.', 'Sulit', 'SUM, JOIN, GROUP BY', $orders, "SELECT pelanggan.nama, AVG(pesanan.total) AS total_belanja\nFROM pelanggan\nJOIN pesanan ON pesanan.pelanggan_id = pelanggan.id\nGROUP BY pelanggan.id, pelanggan.nama\nORDER BY pelanggan.nama;", ['AVG'], "SELECT pelanggan.nama, SUM(pesanan.total) AS total_belanja\nFROM pelanggan\nJOIN pesanan ON pesanan.pelanggan_id = pelanggan.id\nGROUP BY pelanggan.id, pelanggan.nama\nORDER BY pelanggan.nama;", 'AVG menghitung rata-rata. Untuk menjumlahkan semua transaksi pelanggan, fungsi yang tepat adalah SUM.', [['nama' => 'Dimas', 'total_belanja' => 200000], ['nama' => 'Nina', 'total_belanja' => 225000]], ['Baca kata kerja pada permintaan: hitung total seluruh pesanan.', 'AVG menghasilkan rata-rata.', 'Fungsi agregat untuk menjumlahkan adalah SUM.']),
        ];

        return array_map(fn ($case) => $this->tokenize($case), $cases);
    }

    public function case(string $id): ?array
    {
        return $this->cases()[$id] ?? null;
    }

    private function makeCase(string $topic, string $title, string $description, string $difficulty, string $sqlTopic, array $dataset, string $query, array $wrongTokens, string $correctedQuery, string $explanation, array $results, array $hints): array
    {
        $columns = [];
        foreach ($dataset as $table => $rows) {
            $columns[$table] = array_keys($rows[0]);
        }

        return compact('topic', 'title', 'description', 'difficulty', 'sqlTopic', 'dataset', 'columns', 'query', 'wrongTokens', 'correctedQuery', 'explanation', 'results', 'hints');
    }

    private function tokenize(array $case): array
    {
        $correctionMap = ['WHRRE' => 'WHERE', 'ORDERBY' => 'ORDER BY', '>=' => '>', 'nam' => 'nama', "'X MM'" => "'X RPL'", 'OR' => 'AND', 'ASC' => 'DESC', 'nama' => 'kelas', 'WHERE' => 'HAVING', 'pesanan.id' => 'pesanan.pelanggan_id', 'AVG' => 'SUM'];
        $wrongTokens = $case['wrongTokens'];
        $tokens = [];
        $lines = [];
        $index = 0;
        foreach (explode("\n", $case['query']) as $line) {
            preg_match_all("/'[^']*'|[A-Za-z_][A-Za-z0-9_.]*|>=|<=|<>|!=|=|>|<|\\*|\\(|\\)|,|;|\\d+/", $line, $matches, PREG_OFFSET_CAPTURE);
            $lineTokens = [];
            $offset = 0;
            foreach ($matches[0] as [$text, $position]) {
                if ($position > $offset) {
                    $lineTokens[] = ['text' => substr($line, $offset, $position - $offset), 'id' => null];
                }
                $wrongIndex = array_search($text, $wrongTokens, true);
                $isWrong = $wrongIndex !== false;
                if ($isWrong) {
                    unset($wrongTokens[$wrongIndex]);
                }
                $token = ['id' => 'token-'.$index++, 'text' => $text, 'wrong' => $isWrong];
                $tokens[] = $token;
                $lineTokens[] = $token;
                $offset = $position + strlen($text);
            }
            if ($offset < strlen($line)) {
                $lineTokens[] = ['text' => substr($line, $offset), 'id' => null];
            }
            $lines[] = $lineTokens;
        }

        $case['lines'] = $lines;
        $case['tokens'] = $tokens;
        $case['wrongTokenIds'] = array_column(array_filter($tokens, fn ($token) => $token['wrong']), 'id');
        $case['corrections'] = array_map(fn ($wrong) => ['wrong' => $wrong, 'correct' => $correctionMap[$wrong] ?? 'periksa query yang sudah diperbaiki'], $case['wrongTokens']);
        unset($case['wrongTokens']);

        return $case;
    }
}
