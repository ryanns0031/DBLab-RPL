@extends('layout')
@section('title', 'SQL Shopkeeper 3D')
@section('content')
<link rel="stylesheet" href="/assets/sql-shopkeeper.css">
@php($mission = $missions[$highestLevel] ?? $missions[5])
<div class="shopkeeper-page" id="shopkeeper-game"
     data-query-url="{{ $queryUrl }}" data-action-url="{{ $actionUrl }}"
     data-missions="{{ json_encode($missions) }}"
     data-initial-state="{{ json_encode(['inventory' => $gameState['inventory'], 'balance' => $gameState['balance'], 'reputation' => $gameState['reputation'], 'servedCount' => $gameState['served_count'], 'waiting' => $gameState['customer_queue'][0] ?? null, 'queueCount' => count($gameState['customer_queue']), 'highestLevel' => $highestLevel, 'completedLevels' => $gameState['completed_levels'], 'restockedItems' => $gameState['restocked_items'], 'queryPassed' => $gameState['query_passed'], 'xp' => $gameXp, 'tables' => $tables]) }}"
     data-products="{{ json_encode($items) }}"
     data-user-name="{{ auth()->user()->name }}">
    <nav class="shopkeeper-crumb"><a href="/siswa/dashboard">Dashboard</a><span>›</span><a href="{{ $catalogUrl }}">Mini Games SQL</a><span>›</span><b>SQL Shopkeeper 3D</b><a class="shopkeeper-back" href="{{ $catalogUrl }}">← Kembali</a></nav>
    <header class="shopkeeper-topline">
        <div><div class="shopkeeper-brand"><span class="shopkeeper-logo">R</span><span>RPL MART <small>SQL SHOPKEEPER</small></span></div><div class="shopkeeper-day">HARI <b id="shop-day">01</b><i></i> TOKO BUKA <span id="shop-clock">08:00</span></div></div>
        <div class="shopkeeper-hud">
            <div class="shop-hud-card"><span>Saldo Toko</span><b id="shop-balance">Rp {{ number_format($gameState['balance'], 0, ',', '.') }}</b></div>
            <div class="shop-hud-card"><span>Reputasi</span><b><i class="shop-status-dot"></i><span id="shop-reputation">{{ $gameState['reputation'] }}</span>%</b></div>
            <div class="shop-hud-card"><span>XP Game</span><b><span id="shop-xp">{{ $gameXp }}</span> XP</b></div>
            <button type="button" class="shop-sql-open" data-open-terminal>⌘ <span>Terminal SQL</span></button>
        </div>
    </header>

    <div class="shopkeeper-layout">
        <main class="shopkeeper-main">
            <section class="shop-world-card" aria-label="Lingkungan toko 3D">
                <canvas id="shop-world" aria-label="Toko 3D, gerakkan karakter dengan WASD atau joystick"></canvas>
                <div class="shop-world-vignette"></div>
                <div class="world-top-left"><span class="world-live"><i></i> SIMULASI AKTIF</span><span>RPL MART · LANTAI 1</span></div>
                <div class="world-top-right"><button type="button" data-toggle-map aria-label="Tampilkan peta mini">▦</button><button type="button" data-fullscreen aria-label="Layar penuh">⛶</button></div>
                <div class="world-crosshair" aria-hidden="true">+</div>
                <div class="world-interaction" id="world-interaction" aria-live="polite"><span id="world-interaction-key">E</span><span id="world-interaction-text">Dekati objek untuk berinteraksi</span><button id="world-interaction-button" type="button" hidden>Interaksi</button></div>
                <div class="world-object-labels" aria-hidden="true"><span>RAK PRODUK</span><span>KASIR</span><span>GUDANG</span></div>
                <div class="world-mission-pill"><span>✦ MISI HARIAN</span><b id="customer-mission-text">{{ $gameState['served_count'] }}/5 pelanggan dilayani</b><span class="world-mission-track"><i id="customer-progress" style="width:{{ min(100, $gameState['served_count'] * 20) }}%"></i></span></div>
                <div class="world-controls-hint"><kbd>W</kbd><kbd>A</kbd><kbd>S</kbd><kbd>D</kbd> Gerak <kbd>E</kbd> Interaksi <kbd>Shift</kbd> Lari</div>
                <div class="shop-joystick" id="shop-joystick" aria-label="Joystick gerak"><div class="joystick-ring"><span class="joystick-north">⌃</span><span class="joystick-south">⌄</span><span class="joystick-west">‹</span><span class="joystick-east">›</span><i class="joystick-thumb"></i></div></div>
                <div class="shop-touch-actions"><button type="button" data-touch-run aria-label="Lari">⇧</button><button type="button" data-touch-interact aria-label="Interaksi">E</button></div>
                <div class="world-toast" id="shop-toast" role="status" aria-live="polite"></div>
            </section>

            <section class="shop-terminal" id="shop-terminal" aria-label="Terminal SQL">
                <div class="terminal-heading"><div><span class="terminal-icon">⌘</span><div><b>Terminal SQL Toko</b><small>DATABASE LATIHAN · SQLITE IN-MEMORY</small></div></div><div class="terminal-heading-actions"><span class="terminal-ready"><i></i> READY</span><button type="button" data-close-terminal aria-label="Tutup terminal">×</button></div></div>
                <div class="terminal-task"><div><span class="terminal-level" id="terminal-level">LEVEL {{ $highestLevel }} / 5</span><span class="terminal-concept" id="terminal-concept">{{ $mission['concept'] }}</span><h2 id="mission-title">{{ $mission['title'] }}</h2><p id="mission-brief">{{ $mission['brief'] }}</p></div><button type="button" class="terminal-hint" id="shop-hint" data-hint="0">✦ <span>Petunjuk</span></button></div>
                <div class="terminal-editor-wrap"><div class="terminal-line-gutter" id="terminal-lines">1</div><textarea id="shop-query" spellcheck="false" autocapitalize="off" autocomplete="off" aria-label="Editor query SQL" maxlength="4000">{{ (($gameState['last_query']['level'] ?? 0) === $highestLevel) ? ($gameState['last_query']['query'] ?? $mission['starter']) : $mission['starter'] }}</textarea></div>
                <div class="terminal-footer"><span class="terminal-tip">Gunakan SELECT untuk membaca data. Query tidak dapat mengubah database.</span><div><button type="button" class="terminal-run" id="shop-run">▶ Jalankan Query</button><button type="button" class="terminal-submit" id="shop-submit">✓ Submit Misi</button></div></div>
                <div class="terminal-message" id="terminal-message" role="status" aria-live="polite"></div>
                <section class="terminal-results" id="terminal-results" hidden><div class="terminal-results-head"><b>Hasil Query</b><span id="result-count"></span></div><div class="terminal-table-scroll"><table><thead id="result-head"></thead><tbody id="result-body"></tbody></table></div></section>
                <section class="terminal-evidence"><div class="evidence-title"><span>◈</span><div><b>CATATAN TOKO</b><small>DATABASE SCHEMA</small></div><button type="button" id="schema-toggle" aria-expanded="false">Lihat tabel⌄</button></div><div class="schema-list" id="schema-list" hidden><div><b>barang</b><span>id · nama_barang · kategori · stok · harga · stok_minimum · pemasok_id</span></div><div><b>penjualan</b><span>id · pelanggan_id · nama_pelanggan · total · hari</span></div><div><b>detail_penjualan</b><span>id · penjualan_id · barang_id · jumlah · harga_satuan · subtotal</span></div><div><b>pemasok</b><span>id · nama_pemasok · kota</span></div><div><b>pelanggan</b><span>id · nama</span></div></div></section>
            </section>
        </main>

        <aside class="shopkeeper-side">
            <section class="shop-side-card mission-card"><div class="side-card-heading"><span class="side-icon mission-icon">✦</span><div><small>LEVEL {{ $highestLevel }} · {{ ['1' => 'PEMULA', '2' => 'PEMULA', '3' => 'PEMULA', '4' => 'MENENGAH', '5' => 'MENENGAH'][$highestLevel] }}</small><h2>Misi Toko</h2></div><span class="mission-badge">{{ $highestLevel }}/5</span></div><p>{{ $mission['brief'] }}</p><div class="mission-action"><span>LANGKAH BERIKUTNYA</span><b>{{ $mission['action'] }}</b></div><div class="mission-levels">@foreach($missions as $number => $item)<a href="#" class="{{ $number < $highestLevel ? 'is-complete' : ($number === $highestLevel ? 'is-current' : 'is-locked') }}" data-mission-level="{{ $number }}" aria-label="Level {{ $number }}{{ $number > $highestLevel ? ', terkunci' : '' }}">@if($number < $highestLevel)✓@elseif($number === $highestLevel){{ $number }}@else⌑@endif</a>@endforeach</div></section>
            <section class="shop-side-card inventory-card"><div class="side-card-heading"><span class="side-icon stock-icon">▤</span><div><small>GUDANG & RAK</small><h2>Inventaris</h2></div><span class="inventory-count">{{ count($items) }} produk</span></div><div class="inventory-list" id="inventory-list">@foreach($tables['barang'] as $item)<button type="button" class="inventory-item {{ $item['stok'] < $item['stok_minimum'] ? 'is-low' : '' }}" data-item-id="{{ $item['id'] }}"><span class="inventory-art art-{{ $item['id'] }}">{{ [1 => '🥤', 2 => '🍜', 3 => '🍞', 4 => '🥛', 5 => '🧋'][$item['id']] }}</span><span class="inventory-name"><b>{{ $item['nama_barang'] }}</b><small>{{ $item['kategori'] }} · min. {{ $item['stok_minimum'] }}</small></span><span class="inventory-stock"><b data-stock-for="{{ $item['id'] }}">{{ $item['stok'] }}</b><small>stok</small></span></button>@endforeach</div><div class="inventory-legend"><span><i></i> Stok aman</span><span><i></i> Perlu restock</span></div></section>
            <section class="shop-side-card customer-card"><div class="side-card-heading"><span class="side-icon customer-icon">♙</span><div><small>ANTREAN KASIR</small><h2>Pelanggan</h2></div><span class="queue-pill" id="queue-count">{{ count($gameState['customer_queue']) }} menunggu</span></div><div class="waiting-customer" id="waiting-customer">@if($gameState['customer_queue'])<span class="customer-avatar">{{ mb_substr($gameState['customer_queue'][0]['name'], 0, 1) }}</span><span><b id="customer-name">{{ $gameState['customer_queue'][0]['name'] }}</b><small>Mencari {{ $items[$gameState['customer_queue'][0]['item_id']]['nama_barang'] }} · {{ $gameState['customer_queue'][0]['qty'] }} unit</small></span>@else<span class="customer-empty">Antrean pelanggan selesai. Kerja bagus!</span>@endif</div><button type="button" class="serve-customer" data-serve-customer @disabled(!$gameState['customer_queue'])>Layani pelanggan <span>→</span></button><p class="customer-note">Dekati kasir lalu tekan E, atau gunakan tombol ini.</p></section>
            <section class="shop-side-card staff-card"><div class="staff-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</div><div><b>{{ auth()->user()->name }}</b><small>Pemilik RPL Mart</small></div><span class="staff-level">Lv. {{ min(5, $highestLevel) }}</span></section>
        </aside>
    </div>
</div>
<script src="/assets/sql-shopkeeper3d.js" defer></script>
@endsection
