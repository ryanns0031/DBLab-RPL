@extends('layout')
@section('title', 'Ruang Diskusi · Zona Santai')

@section('content')
<div class="discuss-container">
    <nav class="leisure-breadcrumb" aria-label="Breadcrumb">
        <a href="/siswa/dashboard">Dashboard</a>
        <span aria-hidden="true">›</span>
        <a href="{{ route('siswa.leisure') }}">Zona Santai</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Ruang Diskusi</span>
    </nav>

    {{-- Header Banner --}}
    <header class="discuss-hero">
        <div class="discuss-hero-text">
            <h1>Ruang Diskusi</h1>
            <p>Bertanya, berbagi solusi SQL, dan berdiskusi bersama teman sekelas untuk memahami basis data lebih dalam.</p>
        </div>
        <div class="discuss-hero-actions">
            <a href="{{ route('siswa.fun-zone.discussions.create') }}" class="btn btn-primary discuss-btn-create">
                <span class="discuss-btn-icon" aria-hidden="true">✎</span>
                <span>Buat Diskusi</span>
            </a>
        </div>
    </header>

    {{-- Stats Ribbon --}}
    <section class="discuss-stats-ribbon" aria-label="Ringkasan Statistik Forum">
        <div class="discuss-stat-card">
            <span class="discuss-stat-icon all" aria-hidden="true">💬</span>
            <div>
                <strong>{{ number_format($stats['total'], 0, ',', '.') }}</strong>
                <span>Total Diskusi</span>
            </div>
        </div>
        <div class="discuss-stat-card">
            <span class="discuss-stat-icon solved" aria-hidden="true">✓</span>
            <div>
                <strong>{{ number_format($stats['solved'], 0, ',', '.') }}</strong>
                <span>Diskusi Terjawab</span>
            </div>
        </div>
        <div class="discuss-stat-card">
            <span class="discuss-stat-icon unsolved" aria-hidden="true">⏳</span>
            <div>
                <strong>{{ number_format($stats['unsolved'], 0, ',', '.') }}</strong>
                <span>Menunggu Jawaban</span>
            </div>
        </div>
        <div class="discuss-stat-card">
            <span class="discuss-stat-icon replies" aria-hidden="true">👥</span>
            <div>
                <strong>{{ number_format($stats['total_replies'], 0, ',', '.') }}</strong>
                <span>Total Balasan</span>
            </div>
        </div>
    </section>

    {{-- Filter & Search Toolbar --}}
    <section class="panel discuss-toolbar">
        <form method="get" action="{{ route('siswa.fun-zone.discussions') }}" class="discuss-search-bar">
            @if($selectedCategory)
                <input type="hidden" name="category" value="{{ $selectedCategory }}">
            @endif
            @if($selectedStatus)
                <input type="hidden" name="status" value="{{ $selectedStatus }}">
            @endif
            <input type="hidden" name="sort" value="{{ $sort }}">

            <div class="discuss-input-wrap">
                <span class="search-icon" aria-hidden="true">🔍</span>
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari topik diskusi, pertanyaan, atau kode SQL..." class="form-control form-control-sm" aria-label="Pencarian diskusi">
                @if($search)
                    <a href="{{ route('siswa.fun-zone.discussions', array_filter(['category' => $selectedCategory, 'status' => $selectedStatus, 'sort' => $sort !== 'terbaru' ? $sort : null])) }}" class="discuss-search-clear" title="Hapus pencarian">✕</a>
                @endif
            </div>
            <button type="submit" class="btn btn-sm btn-secondary">Cari</button>
        </form>

        <div class="discuss-filter-row">
            {{-- Category Filter Chips --}}
            <div class="discuss-category-chips">
                <a href="{{ route('siswa.fun-zone.discussions', array_filter(['category' => null, 'status' => $selectedStatus, 'sort' => $sort !== 'terbaru' ? $sort : null, 'q' => $search ?: null])) }}" class="discuss-chip {{ !$selectedCategory ? 'active' : '' }}">
                    Semua Kategori
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('siswa.fun-zone.discussions', array_filter(['category' => $cat->slug, 'status' => $selectedStatus, 'sort' => $sort !== 'terbaru' ? $sort : null, 'q' => $search ?: null])) }}" class="discuss-chip {{ $selectedCategory === $cat->slug ? 'active' : '' }}">
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>

            {{-- Status & Sort Controls --}}
            <div class="discuss-controls-right">
                <div class="discuss-status-pills">
                    <a href="{{ route('siswa.fun-zone.discussions', array_filter(['category' => $selectedCategory, 'status' => null, 'sort' => $sort !== 'terbaru' ? $sort : null, 'q' => $search ?: null])) }}" class="discuss-status-pill {{ !$selectedStatus ? 'active' : '' }}">
                        Semua Status
                    </a>
                    <a href="{{ route('siswa.fun-zone.discussions', array_filter(['category' => $selectedCategory, 'status' => 'belum_terjawab', 'sort' => $sort !== 'terbaru' ? $sort : null, 'q' => $search ?: null])) }}" class="discuss-status-pill {{ $selectedStatus === 'belum_terjawab' ? 'active' : '' }}">
                        Belum Terjawab
                    </a>
                    <a href="{{ route('siswa.fun-zone.discussions', array_filter(['category' => $selectedCategory, 'status' => 'terjawab', 'sort' => $sort !== 'terbaru' ? $sort : null, 'q' => $search ?: null])) }}" class="discuss-status-pill {{ $selectedStatus === 'terjawab' ? 'active' : '' }}">
                        Terjawab
                    </a>
                </div>

                <div class="discuss-sort-select-wrap">
                    <label for="sortSelect">Urutkan:</label>
                    <select id="sortSelect" class="form-select form-select-sm" onchange="location.href=this.value;">
                        <option value="{{ route('siswa.fun-zone.discussions', array_filter(['category' => $selectedCategory, 'status' => $selectedStatus, 'sort' => 'terbaru', 'q' => $search ?: null])) }}" @selected($sort === 'terbaru')>Terbaru</option>
                        <option value="{{ route('siswa.fun-zone.discussions', array_filter(['category' => $selectedCategory, 'status' => $selectedStatus, 'sort' => 'terbanyak_dibalas', 'q' => $search ?: null])) }}" @selected($sort === 'terbanyak_dibalas')>Paling Banyak Dibalas</option>
                        <option value="{{ route('siswa.fun-zone.discussions', array_filter(['category' => $selectedCategory, 'status' => $selectedStatus, 'sort' => 'terlama', 'q' => $search ?: null])) }}" @selected($sort === 'terlama')>Terlama</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    {{-- Discussions List --}}
    <main class="discuss-list-section">
        @if($discussions->isEmpty())
            <div class="panel discuss-empty-state">
                <span class="discuss-empty-icon" aria-hidden="true">💬</span>
                <h3>Belum Ada Topik Diskusi</h3>
                <p class="muted">
                    @if($search || $selectedCategory || $selectedStatus)
                        Tidak ada topik yang sesuai dengan filter atau kata kunci pencarianmu.
                    @else
                        Jadilah yang pertama memulai diskusi atau bertanya tentang masalah basis data!
                    @endif
                </p>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    @if($search || $selectedCategory || $selectedStatus)
                        <a href="{{ route('siswa.fun-zone.discussions') }}" class="btn btn-outline-secondary btn-sm">Reset Filter</a>
                    @endif
                    <a href="{{ route('siswa.fun-zone.discussions.create') }}" class="btn btn-primary btn-sm">Buat Diskusi Pertama</a>
                </div>
            </div>
        @else
            <div class="discuss-topics-list">
                @foreach($discussions as $discussion)
                    @php
                        $isSolved = $discussion->isSolved();
                        $catBadge = match($discussion->category->slug) {
                            'sql-dasar' => 'cat-blue',
                            'select-dan-where' => 'cat-teal',
                            'join-dan-relasi' => 'cat-purple',
                            'database-design' => 'cat-indigo',
                            'project-database' => 'cat-amber',
                            'error-dan-debugging' => 'cat-rose',
                            default => 'cat-cyan',
                        };
                    @endphp
                    <article class="panel discuss-topic-card {{ $isSolved ? 'is-solved' : '' }}">
                        <div class="discuss-topic-main">
                            <div class="discuss-topic-meta">
                                <span class="discuss-cat-badge {{ $catBadge }}">
                                    {{ $discussion->category->name }}
                                </span>
                                @if($isSolved)
                                    <span class="discuss-status-badge solved">
                                        ✓ Terjawab
                                    </span>
                                @else
                                    <span class="discuss-status-badge unsolved">
                                        Belum Terjawab
                                    </span>
                                @endif
                                @if($discussion->sql_snippet)
                                    <span class="discuss-code-indicator" title="Memiliki potongan kode SQL">
                                        &lt;SQL&gt;
                                    </span>
                                @endif
                            </div>

                            <h2 class="discuss-topic-title">
                                <a href="{{ route('siswa.fun-zone.discussions.show', ['slug' => $discussion->slug]) }}">
                                    {{ $discussion->title }}
                                </a>
                            </h2>

                            <p class="discuss-topic-snippet">
                                {{ \Illuminate\Support\Str::limit($discussion->content, 180) }}
                            </p>

                            <div class="discuss-topic-footer">
                                <div class="discuss-author-info">
                                    <span class="discuss-avatar">
                                        {{ mb_substr($discussion->user->name, 0, 1) }}
                                    </span>
                                    <span>
                                        <strong>{{ $discussion->user->name }}</strong>
                                        <small class="muted">· {{ $discussion->created_at->diffForHumans() }}</small>
                                    </span>
                                </div>
                                <div class="discuss-counters">
                                    <span title="Dilihat {{ $discussion->views_count }} kali">
                                        👁 {{ $discussion->views_count }}
                                    </span>
                                    <span title="{{ $discussion->replies_count }} balasan">
                                        💬 {{ $discussion->replies_count }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="discuss-topic-side-action">
                            <a href="{{ route('siswa.fun-zone.discussions.show', ['slug' => $discussion->slug]) }}" class="btn btn-sm btn-outline-primary discuss-btn-view">
                                <span>Lihat Diskusi</span>
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="discuss-pagination mt-4">
                {{ $discussions->links() }}
            </div>
        @endif
    </main>
</div>
@endsection

