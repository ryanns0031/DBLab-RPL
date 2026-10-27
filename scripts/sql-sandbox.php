<?php

// A separate short-lived process, with no Laravel bootstrap or application DB credentials.
error_reporting(0);
try {
    $query = trim((json_decode(stream_get_contents(STDIN), true) ?? [])['query'] ?? '');
    if (! preg_match('/^SELECT\b/i', $query) || preg_match('/;\s*\S|--|\/\*|\b(sqlite_|pragma|attach|detach|load_extension|randomblob|zeroblob)\w*/i', $query)) {
        throw new RuntimeException('Untuk tahap ini, gunakan satu perintah SELECT pada tabel latihan.');
    }
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);
    $db->exec("CREATE TABLE siswa(id INTEGER,nama TEXT,kelas TEXT,nilai INTEGER); INSERT INTO siswa VALUES(1,'Andi','XI RPL 1',85),(2,'Rina','XI RPL 1',90),(3,'Budi','XI RPL 1',78),(4,'Dewi','XI RPL 2',88); CREATE TABLE pelanggan(id INTEGER,nama TEXT,kota TEXT);INSERT INTO pelanggan VALUES(1,'Nina','Surabaya'),(2,'Fajar','Bandung'),(3,'Dimas','Surabaya'); CREATE TABLE pesanan(id INTEGER,pelanggan_id INTEGER,total INTEGER);INSERT INTO pesanan VALUES(1,1,150000),(2,3,200000),(3,1,75000);");
    $db->exec('PRAGMA query_only=ON');
    $db->setAuthorizer(function ($action, $a, $b) {
        if ($action === SQLite3::SELECT) {
            return SQLite3::OK;
        }
        if ($action === SQLite3::READ) {
            return in_array($a, ['siswa', 'pelanggan', 'pesanan'], true) ? SQLite3::OK : SQLite3::DENY;
        }
        if ($action === SQLite3::FUNCTION) {
            return in_array(strtolower($b ?? ''), ['count', 'sum', 'avg', 'min', 'max', 'lower', 'upper', 'length', 'round', 'abs', 'coalesce', 'ifnull', 'like', 'substr'], true) ? SQLite3::OK : SQLite3::DENY;
        }

        return SQLite3::DENY;
    });
    $start = microtime(true);
    $res = $db->query($query);
    $rows = [];
    while (($r = $res->fetchArray(SQLITE3_ASSOC)) !== false) {
        $rows[] = $r;
        if (count($rows) > 200) {
            throw new RuntimeException('Hasil melebihi 200 baris. Tambahkan LIMIT.');
        }
    }
    $columns = [];
    for ($i = 0; $i < $res->numColumns(); $i++) {
        $columns[] = $res->columnName($i);
    }
    echo json_encode(['rows' => $rows, 'columns' => $columns, 'ms' => round((microtime(true) - $start) * 1000, 2)]);
} catch (Throwable $e) {
    $msg = $e->getMessage();
    if (preg_match('/\bWHARE\b/i', $query ?? '')) {
        $msg = 'Periksa keyword WHARE. Keyword yang benar adalah WHERE.';
    }echo json_encode(['error' => 'Query belum berhasil. '.$msg]);
}
