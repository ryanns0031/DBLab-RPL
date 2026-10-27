@extends('layout')
@section('title', $fact->title . ' · Database Fun Facts')

@section('content')
<div class="facts-detail-page">
    <nav class="leisure-breadcrumb" aria-label="Breadcrumb">
        <a href="/siswa/dashboard">Dashboard</a>
        <span aria-hidden="true">›</span>
        <a href="{{ route('siswa.leisure') }}">Zona Santai</a>
        <span aria-hidden="true">›</span>
        <a href="{{ route('siswa.fun-zone.database-fun-facts') }}">Database Fun Facts</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">{{ \Illuminate\Support\Str::limit($fact->title, 25) }}</span>
    </nav>

    <div class="facts-detail-layout">
        <main class="facts-detail-main">
            <article class="panel facts-detail-card">
                <header class="facts-detail-header">
                    <div class="facts-detail-meta">
                        <span class="facts-badge badge-{{ match($fact->category) {
                            'Dunia Nyata' => 'real-world',
                            'Teknologi' => 'tech',
                            'Game & Hiburan' => 'game',
                            'Edukasi' => 'education',
                            'Bisnis' => 'business',
                            'Inovasi' => 'innovation',
                            default => 'default',
                        } }}">
                            @switch($fact->category)
                                @case('Dunia Nyata') 🌐 Dunia Nyata @break
                                @case('Game & Hiburan') 🎮 Game & Hiburan @break
                                @case('Bisnis') 💼 Bisnis @break
                                @case('Teknologi') ⚡ Teknologi @break
                                @case('Edukasi') 🎓 Edukasi @break
                                @case('Inovasi') 💡 Inovasi @break
                                @default ❖ {{ $fact->category }}
                            @endswitch
                        </span>

                        <span class="facts-read-indicator">
                            ✓ Status: Sudah dibaca ({{ $readRecord->first_read_at->locale('id')->diffForHumans() }})
                        </span>
                    </div>

                    <h1 class="facts-detail-title">{{ $fact->title }}</h1>
                    <p class="facts-detail-lead">{{ $fact->summary }}</p>
                </header>

                {{-- Hero Visual --}}
                <div class="facts-detail-hero-art" aria-hidden="true">
                    @include('student.leisure.database-fun-facts.partials.illustration', ['type' => $fact->illustration_type, 'category' => $fact->category])
                </div>

                {{-- Main Story & Explanation --}}
                <div class="facts-detail-content">
                    <h2>Penjelasan Lengkap</h2>
                    @foreach(explode("\n\n", $fact->content) as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>

                {{-- Section: Contoh Penerapan di Dunia Nyata --}}
                @if($fact->real_world_example)
                    <section class="facts-detail-section real-world-box">
                        <div class="section-icon-title">
                            <span class="section-mark" aria-hidden="true">🌍</span>
                            <h3>Contoh Penerapan Nyata</h3>
                        </div>
                        <p>{{ $fact->real_world_example }}</p>
                    </section>
                @endif

                {{-- Section: Konsep Database yang Berkaitan --}}
                @if($fact->database_concept)
                    <section class="facts-detail-section concept-box">
                        <div class="section-icon-title">
                            <span class="section-mark" aria-hidden="true">🧠</span>
                            <h3>Konsep Database yang Berkaitan</h3>
                        </div>
                        <p>{{ $fact->database_concept }}</p>
                    </section>
                @endif

                {{-- Section: Hubungannya dengan SQL --}}
                @if($fact->sql_connection)
                    <section class="facts-detail-section sql-box">
                        <div class="section-icon-title">
                            <span class="section-mark code" aria-hidden="true">&lt;/&gt;</span>
                            <h3>Hubungannya dengan SQL</h3>
                        </div>
                        <div class="facts-sql-content">
                            @php
                                // Split text vs markdown code block if present
                                $parts = preg_split('/```(?:sql)?\r?\n?/i', $fact->sql_connection);
                            @endphp
                            @if(count($parts) >= 3)
                                <p>{{ trim($parts[0]) }}</p>
                                <pre class="facts-sql-code"><code>{{ trim($parts[1]) }}</code></pre>
                                @if(isset($parts[2]) && trim($parts[2]) !== '')
                                    <p>{{ trim($parts[2]) }}</p>
                                @endif
                            @else
                                <p>{{ $fact->sql_connection }}</p>
                            @endif
                        </div>
                        <small class="muted facts-sql-note">
                            ℹ️ Skrip SQL di atas adalah materi edukasi dan pemodelan konsep basis data.
                        </small>
                    </section>
                @endif

                {{-- Reference Footer --}}
                @if($fact->source_reference)
                    <footer class="facts-detail-footer">
                        <span class="muted">Referensi & Verifikasi:</span>
                        <cite>{{ $fact->source_reference }}</cite>
                    </footer>
                @endif
            </article>

            <div class="facts-bottom-nav">
                <a href="{{ route('siswa.fun-zone.database-fun-facts') }}" class="btn btn-outline-secondary">
                    ← Kembali ke Database Fun Facts
                </a>
            </div>
        </main>

        {{-- Related Topics Sidebar --}}
        <aside class="facts-detail-sidebar">
            <section class="panel facts-side-panel">
                <div class="facts-panel-title">
                    <span class="facts-panel-icon bulb" aria-hidden="true">💡</span>
                    <h3>Fakta Terkait</h3>
                </div>
                <p class="facts-rec-lead">Eksplorasi topik lain seputar {{ $fact->category }}:</p>

                <div class="facts-rec-list">
                    @forelse($relatedFacts as $related)
                        <a href="{{ route('siswa.fun-zone.database-fun-facts.show', ['slug' => $related->slug]) }}" class="facts-rec-item">
                            <span class="facts-rec-icon" aria-hidden="true">
                                @switch($related->illustration_type)
                                    @case('social-media') 📸 @break
                                    @case('gaming') 🎮 @break
                                    @case('ecommerce') 🛍️ @break
                                    @case('music') 🎵 @break
                                    @case('school') 🏫 @break
                                    @case('transport') 🚗 @break
                                    @case('innovation') 🧠 @break
                                    @default 🗄️
                                @endswitch
                            </span>
                            <span class="facts-rec-title">{{ $related->title }}</span>
                            <span class="facts-rec-arrow" aria-hidden="true">›</span>
                        </a>
                    @empty
                        <p class="muted">Tidak ada fakta lain dalam kategori ini.</p>
                    @endforelse
                </div>

                <a href="{{ route('siswa.fun-zone.database-fun-facts') }}" class="btn btn-primary facts-all-btn mt-3">
                    Jelajahi Semua Topik
                </a>
            </section>
        </aside>
    </div>
</div>
@endsection

