@extends('layout')
@section('title', 'Database Fun Facts')

@section('content')
<div class="facts-page">
    <nav class="leisure-breadcrumb" aria-label="Breadcrumb">
        <a href="/siswa/dashboard">Dashboard</a>
        <span aria-hidden="true">›</span>
        <a href="{{ route('siswa.leisure') }}">Zona Santai</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Database Fun Facts</span>
    </nav>

    {{-- Header Banner --}}
    <header class="facts-hero">
        <div class="facts-hero-content">
            <h1>Database Fun Facts</h1>
            <p>Jelajahi fakta menarik seputar database dan teknologi di dunia nyata. Temukan bagaimana database digunakan di berbagai bidang dan perluas pengetahuanmu!</p>
        </div>
        <div class="facts-hero-art" aria-hidden="true">
            <svg class="facts-banner-svg" viewBox="0 0 320 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="dbGlow" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#38bdf8" />
                        <stop offset="100%" stop-color="#2563eb" />
                    </linearGradient>
                    <linearGradient id="purpleGrad" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#c084fc" />
                        <stop offset="100%" stop-color="#7c3aed" />
                    </linearGradient>
                    <linearGradient id="cardGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#38bdf8" />
                        <stop offset="100%" stop-color="#0284c7" />
                    </linearGradient>
                    <filter id="softGlow" x="-20%" y="-20%" width="140%" height="140%">
                        <feGaussianBlur stdDeviation="8" result="blur" />
                        <feComposite in="SourceGraphic" in2="blur" operator="over" />
                    </filter>
                </defs>

                {{-- Glow Backdrops --}}
                <circle cx="200" cy="85" r="55" fill="#2563eb" opacity="0.35" filter="url(#softGlow)" />
                <circle cx="200" cy="35" r="22" fill="#f59e0b" opacity="0.4" filter="url(#softGlow)" />

                {{-- Lightbulb Badge --}}
                <g transform="translate(186, 12)">
                    <circle cx="14" cy="14" r="14" fill="#fbbf24" opacity="0.2" />
                    <path d="M14 6a7 7 0 0 0-5 12c1.2 1 1.7 2.2 1.7 3.5h6.6c0-1.3.5-2.5 1.7-3.5a7 7 0 0 0-5-12z" fill="#fef08a" stroke="#d97706" stroke-width="1.5" />
                    <rect x="11.5" y="22.5" width="5" height="2" rx="1" fill="#b45309" />
                    <line x1="14" y1="2" x2="14" y2="4" stroke="#fbbf24" stroke-width="2" stroke-linecap="round" />
                    <line x1="5" y1="8" x2="7" y2="9.5" stroke="#fbbf24" stroke-width="2" stroke-linecap="round" />
                    <line x1="23" y1="8" x2="21" y2="9.5" stroke="#fbbf24" stroke-width="2" stroke-linecap="round" />
                </g>

                {{-- Purple Bot Icon Badge --}}
                <g transform="translate(95, 38)">
                    <rect width="48" height="52" rx="14" fill="url(#purpleGrad)" />
                    <circle cx="18" cy="22" r="3.5" fill="#ffffff" />
                    <circle cx="30" cy="22" r="3.5" fill="#ffffff" />
                    <rect x="18" y="32" width="12" height="3" rx="1.5" fill="#ffffff" opacity="0.9" />
                    <rect x="14" y="40" width="20" height="2.5" rx="1" fill="#ffffff" opacity="0.5" />
                </g>

                {{-- Center Database Cylinders --}}
                <g transform="translate(170, 48)">
                    {{-- Base Cylinder --}}
                    <path d="M0 45 C0 37 60 37 60 45 L60 62 C60 70 0 70 0 62 Z" fill="#1d4ed8" />
                    <ellipse cx="30" cy="45" rx="30" ry="8" fill="url(#dbGlow)" />
                    <ellipse cx="30" cy="62" rx="30" ry="7" fill="#1e40af" opacity="0.6" />

                    {{-- Middle Cylinder --}}
                    <path d="M0 25 C0 17 60 17 60 25 L60 42 C60 50 0 50 0 42 Z" fill="#2563eb" />
                    <ellipse cx="30" cy="25" rx="30" ry="8" fill="#60a5fa" />

                    {{-- Top Cylinder --}}
                    <path d="M0 5 C0 -3 60 -3 60 5 L60 22 C60 30 0 30 0 22 Z" fill="#3b82f6" />
                    <ellipse cx="30" cy="5" rx="30" ry="8" fill="#93c5fd" />
                </g>

                {{-- FUN FACTS Clipboard Badge --}}
                <g transform="translate(242, 34)">
                    <rect width="52" height="62" rx="10" fill="url(#cardGrad)" transform="rotate(6 26 31)" />
                    <rect x="6" y="8" width="40" height="46" rx="6" fill="#f8fafc" transform="rotate(6 26 31)" />
                    <text x="14" y="28" font-family="Inter, sans-serif" font-weight="900" font-size="9" fill="#0284c7" letter-spacing="0.5" transform="rotate(6 26 31)">FUN</text>
                    <text x="10" y="40" font-family="Inter, sans-serif" font-weight="900" font-size="8" fill="#0369a1" letter-spacing="0.5" transform="rotate(6 26 31)">FACTS</text>
                </g>

                {{-- Globe --}}
                <g transform="translate(280, 88)" opacity="0.8">
                    <circle cx="16" cy="16" r="16" fill="#0369a1" />
                    <ellipse cx="16" cy="16" rx="8" ry="16" fill="none" stroke="#7dd3fc" stroke-width="1.2" />
                    <line x1="0" y1="16" x2="32" y2="16" stroke="#7dd3fc" stroke-width="1.2" />
                </g>

                {{-- Floating Small Disc --}}
                <g transform="translate(100, 95)" opacity="0.85">
                    <path d="M0 12 C0 6 36 6 36 12 L36 20 C36 26 0 26 0 20 Z" fill="#1e293b" />
                    <ellipse cx="18" cy="12" rx="18" ry="5" fill="#475569" />
                </g>
            </svg>
        </div>
    </header>

    {{-- Category Filters --}}
    <nav class="facts-categories" aria-label="Kategori Fakta">
        @foreach($categories as $cat)
            @php
                $isActive = ($selectedCategory === $cat);
                $catIcon = match($cat) {
                    'Dunia Nyata' => 'globe',
                    'Teknologi' => 'cpu',
                    'Game & Hiburan' => 'game',
                    'Edukasi' => 'school',
                    'Bisnis' => 'business',
                    'Inovasi' => 'spark',
                    default => 'all',
                };
            @endphp
            <a href="{{ route('siswa.fun-zone.database-fun-facts', array_filter(['category' => ($cat === 'Semua' ? null : $cat), 'sort' => ($sort !== 'terbaru' ? $sort : null), 'q' => ($search ?: null)])) }}"
               class="facts-cat-btn {{ $isActive ? 'active' : '' }}"
               data-category="{{ $cat }}">
                <span class="cat-icon cat-icon-{{ $catIcon }}" aria-hidden="true">
                    @switch($catIcon)
                        @case('globe')
                            🌐
                            @break
                        @case('cpu')
                            ⚡
                            @break
                        @case('game')
                            🎮
                            @break
                        @case('school')
                            🎓
                            @break
                        @case('business')
                            💼
                            @break
                        @case('spark')
                            💡
                            @break
                        @default
                            ❖
                    @endswitch
                </span>
                <span>{{ $cat }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Main Layout Grid --}}
    <div class="facts-layout">
        {{-- Left / Main Content: Cards Grid --}}
        <main class="facts-main-column">
            <div class="facts-bar">
                <div>
                    <h2>Fakta Menarik</h2>
                    <small class="muted">Menampilkan {{ $facts->count() }} topik</small>
                </div>
                <div class="facts-bar-actions">
                    <form method="get" action="{{ route('siswa.fun-zone.database-fun-facts') }}" class="facts-search-form">
                        @if($selectedCategory !== 'Semua')
                            <input type="hidden" name="category" value="{{ $selectedCategory }}">
                        @endif
                        <input type="hidden" name="sort" value="{{ $sort }}">
                        <input type="search" name="q" value="{{ $search }}" placeholder="Cari topik fakta..." aria-label="Cari topik fakta" class="facts-search-input">
                        @if($search)
                            <a href="{{ route('siswa.fun-zone.database-fun-facts', array_filter(['category' => ($selectedCategory !== 'Semua' ? $selectedCategory : null), 'sort' => ($sort !== 'terbaru' ? $sort : null)])) }}" class="facts-search-clear" title="Hapus pencarian">✕</a>
                        @endif
                    </form>
                    <div class="facts-sort-wrap">
                        <label for="sortSelect">Urutkan:</label>
                        <select id="sortSelect" class="form-select form-select-sm facts-sort-select" onchange="location.href=this.value;">
                            <option value="{{ route('siswa.fun-zone.database-fun-facts', array_filter(['category' => ($selectedCategory !== 'Semua' ? $selectedCategory : null), 'sort' => 'terbaru', 'q' => ($search ?: null)])) }}" @selected($sort === 'terbaru')>Terbaru</option>
                            <option value="{{ route('siswa.fun-zone.database-fun-facts', array_filter(['category' => ($selectedCategory !== 'Semua' ? $selectedCategory : null), 'sort' => 'terlama', 'q' => ($search ?: null)])) }}" @selected($sort === 'terlama')>Terlama</option>
                            <option value="{{ route('siswa.fun-zone.database-fun-facts', array_filter(['category' => ($selectedCategory !== 'Semua' ? $selectedCategory : null), 'sort' => 'az', 'q' => ($search ?: null)])) }}" @selected($sort === 'az')>Judul A–Z</option>
                        </select>
                    </div>
                </div>
            </div>

            @if($facts->isEmpty())
                <div class="facts-empty panel">
                    <span class="facts-empty-icon" aria-hidden="true">🔍</span>
                    <h3>Tidak ada fakta yang sesuai</h3>
                    <p class="muted">Coba gunakan kata kunci pencarian lain atau pilih kategori Semua.</p>
                    <a href="{{ route('siswa.fun-zone.database-fun-facts') }}" class="btn btn-sm btn-primary">Lihat Semua Fakta</a>
                </div>
            @else
                <div class="facts-cards-grid">
                    @foreach($facts as $fact)
                        @php
                            $isRead = $fact->reads->isNotEmpty();
                            $catClass = match($fact->category) {
                                'Dunia Nyata' => 'badge-real-world',
                                'Teknologi' => 'badge-tech',
                                'Game & Hiburan' => 'badge-game',
                                'Edukasi' => 'badge-education',
                                'Bisnis' => 'badge-business',
                                'Inovasi' => 'badge-innovation',
                                default => 'badge-default',
                            };
                        @endphp
                        <article class="facts-card">
                            <div class="facts-card-header">
                                <span class="facts-badge {{ $catClass }}">
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
                                @if($isRead)
                                    <span class="facts-read-badge" title="Fakta ini sudah kamu baca">✓ Sudah dibaca</span>
                                @endif
                            </div>

                            <div class="facts-card-body">
                                <div class="facts-card-text">
                                    <h3 class="facts-card-title">{{ $fact->title }}</h3>
                                    <p class="facts-card-summary">{{ $fact->summary }}</p>
                                </div>

                                <div class="facts-card-art" aria-hidden="true">
                                    @include('student.leisure.database-fun-facts.partials.illustration', ['type' => $fact->illustration_type, 'category' => $fact->category])
                                </div>
                            </div>

                            <div class="facts-card-footer">
                                <a href="{{ route('siswa.fun-zone.database-fun-facts.show', ['slug' => $fact->slug]) }}" class="facts-read-btn">
                                    <span>Baca Selengkapnya</span>
                                    <span class="btn-arrow" aria-hidden="true">→</span>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </main>

        {{-- Right Column / Sidebar: Progress, Recommendations, Tahukah Kamu --}}
        <aside class="facts-sidebar">
            {{-- Panel 1: Progress Eksplorasi --}}
            <section class="facts-side-panel facts-progress-panel">
                <div class="facts-panel-title">
                    <span class="facts-panel-icon target" aria-hidden="true">🎯</span>
                    <h3>Progress Eksplorasi</h3>
                </div>
                <div class="facts-progress-body">
                    <div class="facts-ring-wrap" style="--p: {{ $progressPercent }};">
                        <svg class="facts-ring-svg" viewBox="0 0 100 100">
                            <circle class="ring-bg" cx="50" cy="50" r="40" />
                            <circle class="ring-val" cx="50" cy="50" r="40" stroke-dasharray="251.2" stroke-dashoffset="{{ 251.2 - (251.2 * $progressPercent / 100) }}" />
                        </svg>
                        <div class="ring-text">
                            <strong>{{ $progressPercent }}%</strong>
                        </div>
                    </div>
                    <div class="facts-progress-info">
                        <b>{{ $readCount }} / {{ $totalFacts }}</b>
                        <span>Fakta telah dibaca</span>
                        @if($progressPercent === 0)
                            <small class="muted">Mulai eksplorasi fakta pertamamu!</small>
                        @elseif($progressPercent === 100)
                            <small class="text-success">Luar biasa! Semua fakta telah selesai dibaca.</small>
                        @else
                            <small class="muted">{{ $totalFacts - $readCount }} topik tersisa</small>
                        @endif
                    </div>
                </div>
            </section>

            {{-- Panel 2: Rekomendasi Topik --}}
            <section class="facts-side-panel facts-rec-panel">
                <div class="facts-panel-title">
                    <span class="facts-panel-icon bulb" aria-hidden="true">💡</span>
                    <h3>Rekomendasi Topik</h3>
                </div>
                <p class="facts-rec-lead">Topik menarik yang bisa kamu jelajahi selanjutnya:</p>

                <div class="facts-rec-list">
                    @forelse($recommendations as $rec)
                        <a href="{{ route('siswa.fun-zone.database-fun-facts.show', ['slug' => $rec->slug]) }}" class="facts-rec-item">
                            <span class="facts-rec-icon" aria-hidden="true">
                                @switch($rec->illustration_type)
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
                            <span class="facts-rec-title">{{ $rec->title }}</span>
                            <span class="facts-rec-arrow" aria-hidden="true">›</span>
                        </a>
                    @empty
                        <p class="muted">Belum ada rekomendasi lanjutan.</p>
                    @endforelse
                </div>

                <a href="{{ route('siswa.fun-zone.database-fun-facts') }}" class="btn btn-primary facts-all-btn">
                    <span aria-hidden="true">✈</span>
                    <span>Lihat Semua Topik</span>
                </a>
            </section>

            {{-- Panel 3: Tahukah Kamu? --}}
            <section class="facts-side-panel facts-trivia-panel">
                <div class="facts-panel-title">
                    <span class="facts-panel-icon book" aria-hidden="true">📖</span>
                    <h3>Tahukah Kamu?</h3>
                </div>
                <div class="facts-trivia-content" id="triviaContainer">
                    <p class="facts-trivia-text" id="triviaText">{{ $trivia['text'] }}</p>
                    <small class="facts-trivia-source" id="triviaSource">Sumber: {{ $trivia['source'] }}</small>
                </div>

                <div class="facts-trivia-actions">
                    <button type="button" class="btn btn-sm btn-outline-secondary facts-next-trivia-btn" id="nextTriviaBtn">
                        <span aria-hidden="true">↻</span> Fakta Berikutnya
                    </button>
                </div>

                <div class="facts-trivia-art" aria-hidden="true">
                    <svg viewBox="0 0 200 100" fill="none" class="facts-globe-svg">
                        <defs>
                            <linearGradient id="globeGrad" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#38bdf8" />
                                <stop offset="100%" stop-color="#1d4ed8" />
                            </linearGradient>
                            <linearGradient id="cylGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#60a5fa" />
                                <stop offset="100%" stop-color="#1e40af" />
                            </linearGradient>
                        </defs>
                        {{-- 3D Globe --}}
                        <circle cx="70" cy="50" r="34" fill="url(#globeGrad)" />
                        <ellipse cx="70" cy="50" rx="16" ry="34" fill="none" stroke="#bae6fd" stroke-width="1.5" opacity="0.6" />
                        <line x1="36" y1="50" x2="104" y2="50" stroke="#bae6fd" stroke-width="1.5" opacity="0.6" />
                        <line x1="43" y1="32" x2="97" y2="32" stroke="#bae6fd" stroke-width="1.2" opacity="0.4" />
                        <line x1="43" y1="68" x2="97" y2="68" stroke="#bae6fd" stroke-width="1.2" opacity="0.4" />

                        {{-- 3D Database Cylinders --}}
                        <g transform="translate(130, 30)">
                            {{-- Bottom --}}
                            <path d="M0 32 C0 26 44 26 44 32 L44 46 C44 52 0 52 0 46 Z" fill="#1e3a8a" />
                            <ellipse cx="22" cy="32" rx="22" ry="6" fill="#2563eb" />
                            {{-- Middle --}}
                            <path d="M0 16 C0 10 44 10 44 16 L44 30 C44 36 0 36 0 30 Z" fill="#2563eb" />
                            <ellipse cx="22" cy="16" rx="22" ry="6" fill="#3b82f6" />
                            {{-- Top --}}
                            <path d="M0 0 C0 -6 44 -6 44 0 L44 14 C44 20 0 20 0 14 Z" fill="#3b82f6" />
                            <ellipse cx="22" cy="0" rx="22" ry="6" fill="#93c5fd" />
                        </g>
                    </svg>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    // Interactive Trivia Rotator
    const triviaList = @json($triviaList);
    let currentIndex = 0;
    const textEl = document.getElementById('triviaText');
    const sourceEl = document.getElementById('triviaSource');
    const btnEl = document.getElementById('nextTriviaBtn');

    if (btnEl && textEl && sourceEl && triviaList.length > 1) {
        btnEl.addEventListener('click', () => {
            currentIndex = (currentIndex + 1) % triviaList.length;
            textEl.style.opacity = '0';
            sourceEl.style.opacity = '0';
            setTimeout(() => {
                textEl.textContent = triviaList[currentIndex].text;
                sourceEl.textContent = 'Sumber: ' + triviaList[currentIndex].source;
                textEl.style.opacity = '1';
                sourceEl.style.opacity = '1';
            }, 180);
        });
    }
})();
</script>
@endpush

