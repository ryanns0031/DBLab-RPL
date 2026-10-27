<?php

namespace App\Services;

class SqlShopkeeperGame
{
    public const ITEMS = [
        1 => ['nama_barang' => 'Air Mineral', 'kategori' => 'Minuman', 'harga' => 3000, 'biaya' => 1800, 'stok_minimum' => 5, 'pemasok_id' => 1],
        2 => ['nama_barang' => 'Mie Instan', 'kategori' => 'Makanan', 'harga' => 3500, 'biaya' => 2200, 'stok_minimum' => 5, 'pemasok_id' => 2],
        3 => ['nama_barang' => 'Roti Cokelat', 'kategori' => 'Makanan', 'harga' => 7000, 'biaya' => 4500, 'stok_minimum' => 5, 'pemasok_id' => 3],
        4 => ['nama_barang' => 'Susu UHT', 'kategori' => 'Minuman', 'harga' => 6000, 'biaya' => 4000, 'stok_minimum' => 5, 'pemasok_id' => 1],
        5 => ['nama_barang' => 'Es Teh Manis', 'kategori' => 'Minuman', 'harga' => 5000, 'biaya' => 2800, 'stok_minimum' => 5, 'pemasok_id' => 2],
    ];

    public function initialState(): array
    {
        return [
            'inventory' => [1 => 2, 2 => 3, 3 => 4, 4 => 15, 5 => 12],
            'balance' => 75000,
            'reputation' => 72,
            'day' => 1,
            'served_count' => 0,
            'customer_queue' => [
                ['name' => 'Naya', 'item_id' => 4, 'qty' => 1],
                ['name' => 'Bimo', 'item_id' => 1, 'qty' => 2],
                ['name' => 'Salsa', 'item_id' => 3, 'qty' => 1],
                ['name' => 'Rafi', 'item_id' => 2, 'qty' => 2],
                ['name' => 'Tari', 'item_id' => 5, 'qty' => 1],
            ],
            'sales' => [
                ['customer_id' => 1, 'items' => [['item_id' => 1, 'qty' => 12], ['item_id' => 2, 'qty' => 4]]],
                ['customer_id' => 2, 'items' => [['item_id' => 2, 'qty' => 9], ['item_id' => 3, 'qty' => 5]]],
                ['customer_id' => 3, 'items' => [['item_id' => 1, 'qty' => 7], ['item_id' => 4, 'qty' => 6]]],
            ],
            'restocked_items' => [],
            'query_passed' => [],
            'completed_levels' => [],
            'last_query' => null,
        ];
    }

    public function tables(array $state): array
    {
        $items = [];
        foreach (self::ITEMS as $id => $item) {
            $items[] = ['id' => $id] + $item + ['stok' => (int) ($state['inventory'][$id] ?? 0)];
        }

        $sales = [];
        $details = [];
        $customerNames = [1 => 'Naya', 2 => 'Bimo', 3 => 'Salsa', 4 => 'Rafi', 5 => 'Tari'];
        foreach ($state['sales'] as $index => $sale) {
            $total = 0;
            foreach ($sale['items'] as $line) {
                $item = self::ITEMS[$line['item_id']];
                $subtotal = $item['harga'] * $line['qty'];
                $total += $subtotal;
                $details[] = ['id' => count($details) + 1, 'penjualan_id' => $index + 1, 'barang_id' => $line['item_id'], 'jumlah' => $line['qty'], 'harga_satuan' => $item['harga'], 'subtotal' => $subtotal];
            }
            $sales[] = ['id' => $index + 1, 'pelanggan_id' => $sale['customer_id'], 'nama_pelanggan' => $customerNames[$sale['customer_id']] ?? 'Pelanggan', 'total' => $total, 'hari' => 1 + intdiv($index, 5)];
        }

        return [
            'barang' => $items,
            'penjualan' => $sales,
            'detail_penjualan' => $details,
            'pelanggan' => array_map(fn ($id, $name) => ['id' => $id, 'nama' => $name], array_keys($customerNames), array_values($customerNames)),
            'pemasok' => [
                ['id' => 1, 'nama_pemasok' => 'Segar Jaya', 'kota' => 'Bandung'],
                ['id' => 2, 'nama_pemasok' => 'Sumber Makmur', 'kota' => 'Jakarta'],
                ['id' => 3, 'nama_pemasok' => 'Roti Kita', 'kota' => 'Cimahi'],
            ],
        ];
    }

    public function expected(int $level, array $state): array
    {
        $tables = $this->tables($state);
        $result = match ($level) {
            1 => $this->lowStock($tables['barang']),
            2 => $this->salesRanking($tables),
            3 => $this->salesSummary($tables['penjualan']),
            4 => $this->supplierJoin($tables),
            5 => $this->priorityRestock($tables['barang'], $tables['pemasok']),
            default => [],
        };

        return $result;
    }

    public function queryCorrect(int $level, array $result, array $state): bool
    {
        $expected = $this->expected($level, $state);
        if (! $expected || $result['columns'] !== array_keys($expected[0])) {
            return false;
        }

        $actualRows = array_map(fn ($row) => array_values($row), $result['rows']);
        $expectedRows = array_map(fn ($row) => array_values($row), $expected);

        return array_map(fn ($r) => array_map(fn ($v) => $v === null ? null : (string) $v, $r), $actualRows)
            === array_map(fn ($r) => array_map(fn ($v) => $v === null ? null : (string) $v, $r), $expectedRows);
    }

    private function lowStock(array $items): array
    {
        $rows = array_values(array_filter($items, fn ($item) => $item['stok'] < 5));
        usort($rows, fn ($a, $b) => [$a['stok'], $a['nama_barang']] <=> [$b['stok'], $b['nama_barang']]);

        return array_map(fn ($item) => ['nama_barang' => $item['nama_barang'], 'stok' => $item['stok']], $rows);
    }

    private function salesRanking(array $tables): array
    {
        $totals = [];
        foreach ($tables['detail_penjualan'] as $line) {
            $totals[$line['barang_id']] = ($totals[$line['barang_id']] ?? 0) + $line['jumlah'];
        }
        $rows = [];
        foreach ($totals as $id => $qty) {
            $rows[] = ['nama_barang' => self::ITEMS[$id]['nama_barang'], 'total_terjual' => $qty];
        }
        usort($rows, fn ($a, $b) => [-$a['total_terjual'], $a['nama_barang']] <=> [-$b['total_terjual'], $b['nama_barang']]);

        return $rows;
    }

    private function salesSummary(array $sales): array
    {
        return [[
            'jumlah_transaksi' => count($sales),
            'total_pemasukan' => array_sum(array_column($sales, 'total')),
        ]];
    }

    private function supplierJoin(array $tables): array
    {
        $suppliers = [];
        foreach ($tables['pemasok'] as $supplier) {
            $suppliers[$supplier['id']] = $supplier['nama_pemasok'];
        }
        $rows = [];
        foreach ($tables['barang'] as $item) {
            if ($item['stok'] < $item['stok_minimum']) {
                $rows[] = ['nama_barang' => $item['nama_barang'], 'stok' => $item['stok'], 'nama_pemasok' => $suppliers[$item['pemasok_id']]];
            }
        }
        usort($rows, fn ($a, $b) => [$a['stok'], $a['nama_barang']] <=> [$b['stok'], $b['nama_barang']]);

        return $rows;
    }

    private function priorityRestock(array $items, array $suppliers): array
    {
        $byId = [];
        foreach ($suppliers as $supplier) {
            $byId[$supplier['id']] = $supplier['nama_pemasok'];
        }
        $rows = [];
        foreach ($items as $item) {
            if ($item['stok'] < $item['stok_minimum']) {
                $rows[] = ['nama_barang' => $item['nama_barang'], 'stok' => $item['stok'], 'stok_minimum' => $item['stok_minimum'], 'prioritas' => $item['stok_minimum'] - $item['stok'], 'nama_pemasok' => $byId[$item['pemasok_id']]];
            }
        }
        usort($rows, fn ($a, $b) => [-$a['prioritas'], $a['nama_barang']] <=> [-$b['prioritas'], $b['nama_barang']]);

        return $rows;
    }
}
