<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class SqlShopkeeperSandbox
{
    public function __construct(private readonly SqlShopkeeperGame $game) {}

    public function run(string $query, array $state): array
    {
        if (strlen($query) > 4000) {
            throw new \InvalidArgumentException('Query maksimal 4.000 karakter.');
        }

        $process = new Process([
            config('dblab.sandbox_php_binary', PHP_BINARY),
            '-d', 'memory_limit=48M',
            base_path('scripts/sql-shopkeeper-sandbox.php'),
        ]);
        $process->setInput(json_encode(['query' => $query, 'tables' => $this->game->tables($state)]));
        $process->setTimeout(3);
        try {
            $process->run();
        } catch (\Throwable) {
            throw new \InvalidArgumentException('Query terlalu berat. Sederhanakan query Anda.');
        }

        $result = json_decode($process->getOutput(), true);
        if (! $process->isSuccessful() || ! is_array($result)) {
            throw new \InvalidArgumentException('Sandbox toko belum tersedia. Periksa ekstensi SQLite3.');
        }
        if (isset($result['error'])) {
            throw new \InvalidArgumentException($result['error']);
        }

        return $result;
    }
}
