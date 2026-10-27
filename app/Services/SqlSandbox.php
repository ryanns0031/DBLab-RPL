<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class SqlSandbox
{
    public function run(string $query): array
    {
        if (strlen($query) > 4000) {
            throw new \InvalidArgumentException('Query maksimal 4.000 karakter.');
        }
        $p = new Process([config('dblab.sandbox_php_binary', PHP_BINARY), '-d', 'memory_limit=48M', base_path('scripts/sql-sandbox.php')]);
        $p->setInput(json_encode(['query' => $query]));
        $p->setTimeout(3);
        try {
            $p->run();
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('Query terlalu berat. Sederhanakan query Anda.');
        }
        $result = json_decode($p->getOutput(), true);
        if (! $p->isSuccessful() || ! is_array($result)) {
            throw new \InvalidArgumentException('Sandbox belum tersedia. Periksa ekstensi SQLite3.');
        }
        if (isset($result['error'])) {
            throw new \InvalidArgumentException($result['error']);
        }

return $result;
    }

    public function equivalent(array $a, array $b): bool
    {
        $normalize = function ($rows) {
            $out = array_map(fn ($r) => json_encode(array_map(fn ($v) => $v === null ? null : (string) $v, array_values($r))), $rows);
            sort($out);

            return $out;
        };

        return $normalize($a) === $normalize($b);
    }
}
