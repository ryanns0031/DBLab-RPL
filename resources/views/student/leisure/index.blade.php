@extends('layout')
@section('title', 'Zona Santai')
@section('content')
<div class="leisure-heading">
    <div>
        <nav class="leisure-breadcrumb" aria-label="Breadcrumb"><a href="/siswa/dashboard">Dashboard</a><span aria-hidden="true">›</span><span aria-current="page">Zona Santai</span></nav>
        <h1>Zona Santai</h1>
        <p class="leisure-lead">Belajar SQL dengan cara yang lebih ringan. Pilih aktivitas favoritmu!</p>
    </div>
    <div class="leisure-stats" aria-label="Ringkasan aktivitas">
        <article class="leisure-stat"><span class="leisure-stat-icon xp" aria-hidden="true">✦</span><div><strong>{{ number_format($xp, 0, ',', '.') }}</strong><span>Total XP</span><small>Dari challenge selesai</small></div></article>
        <article class="leisure-stat"><span class="leisure-stat-icon games" aria-hidden="true">▧</span><div><strong>{{ number_format($publishedShowcaseCount) }}</strong><span>Karya dipamerkan</span><small>Karya teman yang disetujui</small></div></article>
        <article class="leisure-stat"><span class="leisure-stat-icon badges" aria-hidden="true">◇</span><div><strong>{{ $badgeCount }}</strong><span>Badge terkumpul</span><small>Badge WHERE Hunter</small></div></article>
    </div>
</div>

<div class="leisure-grid">
    @foreach($activities as $slug => $activity)
        <article class="leisure-card theme-{{ $activity['theme'] }}">
            <div class="leisure-art" aria-hidden="true">
                @switch($activity['icon'])
                    @case('query')
                        <img src="/assets/tebak-hasil-query.png" alt="" loading="lazy">
                        @break
                    @case('detective')
                        <img src="/assets/query-detective.png" alt="" loading="lazy">
                        @break
                    @case('facts')
                        <img src="/assets/database-fun-facts.png" alt="" loading="lazy">
                        @break
                    @case('escape')
                        <svg viewBox="0 0 240 120"><path d="M69 103V24h94v79" class="art-door"/><path d="M85 103V38h62v65" class="art-door-inner"/><circle cx="135" cy="72" r="3" class="art-key"/><circle cx="184" cy="47" r="11" class="art-key"/><path d="M184 58v25m0-8h8m-8 3h-7" class="art-handle"/><path d="M45 104h150" class="art-line"/></svg>
                        @break
                    @case('discussion')
                        <img src="/assets/ruang-diskusi.png" alt="" loading="lazy">
                        @break
                    @case('gallery')
                        <img src="/assets/pameran-karya.png" alt="" loading="lazy">
                        @break
                    @default
                        <svg viewBox="0 0 240 120"><path d="M78 40h84a16 16 0 0 1 15 11l11 30a13 13 0 0 1-20 14l-20-14H82L62 95a13 13 0 0 1-20-14l11-30a16 16 0 0 1 15-11Z" class="art-gamepad"/><path d="M76 58v23m-11-12h23m54-8h.1m17 18h.1" class="art-mark"/><text x="105" y="29" class="art-sql">SQL</text></svg>
                @endswitch
            </div>
            <h2>{{ $activity['title'] }}</h2>
            <p>{{ $activity['description'] }}</p>
            <a class="leisure-action" href="{{ match($slug) { 'tebak-query' => route('siswa.fun-zone.query-guess'), 'query-detective' => route('siswa.fun-zone.query-detective'), 'database-fun-facts' => route('siswa.fun-zone.database-fun-facts'), 'ruang-diskusi' => route('siswa.fun-zone.discussions'), default => route('siswa.leisure.activity', ['activity' => $slug]) } }}"><span>{{ $activity['action'] }}</span><span aria-hidden="true">→</span></a>
        </article>
    @endforeach
</div>
@endsection
