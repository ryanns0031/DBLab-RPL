<?php

// Isolated in-memory store database. It receives only a sanitized game snapshot,
// never Laravel configuration, credentials, or a connection to the application DB.
error_reporting(0);

try {
    $input = json_decode(stream_get_contents(STDIN), true) ?? [];
    $query = trim($input['query'] ?? '');
    if (! preg_match('/^SELECT\b/i', $query) || preg_match('/;\s*\S|--|\/\*|\b(sqlite_|pragma|attach|detach|load_extension|randomblob|zeroblob)\w*/i', $query)) {
        throw new RuntimeException('Gunakan satu query SELECT pada tabel latihan toko.');
    }

    $schemas = [
        'barang' => ['id' => 'INTEGER', 'nama_barang' => 'TEXT', 'kategori' => 'TEXT', 'stok' => 'INTEGER', 'harga' => 'INTEGER', 'stok_minimum' => 'INTEGER', 'pemasok_id' => 'INTEGER'],
        'penjualan' => ['id' => 'INTEGER', 'pelanggan_id' => 'INTEGER', 'nama_pelanggan' => 'TEXT', 'total' => 'INTEGER', 'hari' => 'INTEGER'],
        'detail_penjualan' => ['id' => 'INTEGER', 'penjualan_id' => 'INTEGER', 'barang_id' => 'INTEGER', 'jumlah' => 'INTEGER', 'harga_satuan' => 'INTEGER', 'subtotal' => 'INTEGER'],
        'pelanggan' => ['id' => 'INTEGER', 'nama' => 'TEXT'],
        'pemasok' => ['id' => 'INTEGER', 'nama_pemasok' => 'TEXT', 'kota' => 'TEXT'],
    ];
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);
    $db->busyTimeout(1000);
    foreach ($schemas as $table => $columns) {
        $defs = [];
        foreach ($columns as $column => $type) {
            $defs[] = '"'.$column.'" '.$type;
        }
        $db->exec('CREATE TABLE "'.$table.'" ('.implode(',', $defs).')');
        $insert = $db->prepare('INSERT INTO "'.$table.'" VALUES ('.implode(',', array_fill(0, count($columns), '?')).')');
        foreach (($input['tables'][$table] ?? []) as $row) {
            $index = 1;
            foreach ($columns as $column => $type) {
                $insert->bindValue($index++, $row[$column] ?? null, $type === 'INTEGER' ? SQLITE3_INTEGER : SQLITE3_TEXT);
            }
            $insert->execute();
            $insert->reset();
        }
    }
    $db->setAuthorizer(function ($action, $arg1, $arg2) {
        if ($action === SQLite3::SELECT) {
            return SQLite3::OK;
        }
        if ($action === SQLite3::READ) {
            return in_array($arg1, ['barang', 'penjualan', 'detail_penjualan', 'pelanggan', 'pemasok'], true) ? SQLite3::OK : SQLite3::DENY;
        }
        if ($action === SQLite3::FUNCTION) {
            return in_array(strtolower($arg2 ?? ''), ['count', 'sum', 'avg', 'min', 'max', 'lower', 'upper', 'length', 'round', 'abs', 'coalesce', 'ifnull', 'like', 'substr'], true) ? SQLite3::OK : SQLite3::DENY;
        }

        return SQLite3::DENY;
    });

    $started = microtime(true);
    $statement = $db->query($query);
    $rows = [];
    while (($row = $statement->fetchArray(SQLITE3_ASSOC)) !== false) {
        $rows[] = $row;
        if (count($rows) > 200) {
            throw new RuntimeException('Hasil melebihi 200 baris. Tambahkan LIMIT.');
        }
    }
    $columns = [];
    for ($i = 0; $i < $statement->numColumns(); $i++) {
        $columns[] = $statement->columnName($i);
    }
    echo json_encode(['rows' => $rows, 'columns' => $columns, 'ms' => round((microtime(true) - $started) * 1000, 2)]);
} catch (Throwable $error) {
    echo json_encode(['error' => 'Query belum berhasil. '.$error->getMessage()]);
}
