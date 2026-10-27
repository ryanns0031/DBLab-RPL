@extends('layout')
@section('title', $activity['title'])
@section('content')
<nav class="leisure-breadcrumb" aria-label="Breadcrumb"><a href="/siswa/dashboard">Dashboard</a><span aria-hidden="true">›</span><a href="{{ route('siswa.leisure') }}">Zona Santai</a><span aria-hidden="true">›</span><span aria-current="page">{{ $activity['title'] }}</span></nav>
<div class="leisure-activity-head"><span class="leisure-kicker">ZONA SANTAI</span><h1>{{ $activity['title'] }}</h1><p class="leisure-lead">{{ $activity['description'] }}</p></div>

@if($slug === 'tebak-query' || $slug === 'query-detective')
    <section class="panel leisure-workspace">
        <div class="leisure-prompt"><span class="leisure-kicker">{{ $slug === 'tebak-query' ? 'BACA QUERY' : 'TEMUKAN PERBAIKAN' }}</span><h2>{{ $details['question'] }}</h2></div>
        <pre class="leisure-code"><code>{{ $details['query'] }}</code></pre>
        @if($slug === 'tebak-query')<p class="muted">Data latihan: XI RPL 1 memiliki Andi (85), Rina (90), dan Budi (78). Dewi (88) ada di XI RPL 2.</p>@else<p class="muted">Ingat, nilai teks pada SQL harus ditulis di antara tanda petik.</p>@endif
        <form method="post" action="{{ route('siswa.leisure.submit', ['activity' => $slug]) }}" class="leisure-answers">
            @csrf
            @foreach($details['options'] as $key => $option)
                <label class="leisure-answer"><input type="radio" name="answer" value="{{ $key }}" @checked(old('answer') === $key) required><span class="answer-key">{{ strtoupper($key) }}</span><span>{{ $option }}</span></label>
            @endforeach
            <button class="btn btn-primary">Periksa jawaban</button>
        </form>
    </section>
@elseif($slug === 'database-escape-room')
    <section class="panel leisure-workspace">
        <div class="leisure-mission"><span class="leisure-kicker">MISI 1 · TERMINAL RUANG SERVER</span><h2>{{ $details['mission'] }}</h2><p class="muted">Petunjuk: {{ $details['hint'] }}</p></div>
        @include('partials.schema')
        <form method="post" action="{{ route('siswa.leisure.submit', ['activity' => $slug]) }}" class="leisure-sql-form">
            @csrf
            <label for="escape-query" class="form-label">Tulis query SQL-mu</label>
            <textarea id="escape-query" name="query" class="form-control leisure-query" maxlength="4000" required>{{ old('query', 'SELECT nama FROM pelanggan WHERE kota = \'Surabaya\';') }}</textarea>
            <button class="btn btn-primary mt-3">Jalankan query</button>
        </form>
        <p class="leisure-safety">Query berjalan pada SQLite sandbox terisolasi. Hanya satu SELECT diizinkan; database aplikasi tidak tersentuh.</p>
    </section>
@elseif($slug === 'database-fun-facts')
    <div class="leisure-facts">
        @foreach($details['facts'] as $fact)
            <article class="panel leisure-fact"><span class="leisure-fact-mark" aria-hidden="true">▦</span><div><h2>{{ $fact['title'] }}</h2><p>{{ $fact['body'] }}</p></div></article>
        @endforeach
    </div>
@elseif($slug === 'mini-games-sql')
    <section class="leisure-catalog">
        @foreach($details['games'] as $game)
            @if($game === 'sql-shopkeeper')
                <a class="panel leisure-catalog-item shopkeeper-catalog-card" href="{{ route('siswa.fun-zone.sql-shopkeeper') }}"><span class="leisure-catalog-icon" aria-hidden="true">🏪</span><span><b>SQL Shopkeeper 3D</b><small>Kelola toko, layani pelanggan, dan gunakan SQL untuk membuat keputusan bisnis. · Simulasi 3D · Pemula</small>@if($shopkeeperProgress)<small class="shopkeeper-progress">Progres: Level {{ min(5, $shopkeeperProgress->highest_level) }}/5 · {{ $shopkeeperProgress->game_xp }} XP</small>@else<small class="shopkeeper-progress">Belum dimainkan · progres tersimpan per akun</small>@endif</span><span aria-hidden="true">Mainkan →</span></a>
            @else
                <a class="panel leisure-catalog-item" href="{{ $game === 'tebak-query' ? route('siswa.fun-zone.query-guess') : ($game === 'query-detective' ? route('siswa.fun-zone.query-detective') : route('siswa.leisure.activity', ['activity' => $game])) }}"><span class="leisure-catalog-icon" aria-hidden="true">⌘</span><span><b>{{ ['tebak-query' => 'Tebak Hasil Query', 'query-detective' => 'Query Detective', 'database-escape-room' => 'Database Escape Room'][$game] }}</b><small>{{ ['tebak-query' => 'Tebak baris yang lolos kondisi SQL.', 'query-detective' => 'Perbaiki query yang salah.', 'database-escape-room' => 'Selesaikan misi di sandbox SQL.'][$game] }}</small></span><span aria-hidden="true">→</span></a>
            @endif
        @endforeach
    </section>
@else
    <section class="panel leisure-empty-state"><span class="leisure-empty-icon" aria-hidden="true">▧</span><h2>Belum ada karya publik</h2><p>Belum ada project yang ditandai untuk dibagikan ke Pameran Karya. Project dan pengumpulan akademik siswa tetap bersifat privat.</p><a class="btn btn-outline-primary" href="{{ route('siswa.leisure') }}">Kembali ke Zona Santai</a></section>
@endif

<a class="leisure-back" href="{{ route('siswa.leisure') }}">← Kembali ke Zona Santai</a>
@endsection
