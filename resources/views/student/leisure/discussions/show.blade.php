@extends('layout')
@section('title', $discussion->title . ' · Ruang Diskusi')

@section('content')
<div class="discuss-container discuss-detail-container">
    <nav class="leisure-breadcrumb" aria-label="Breadcrumb">
        <a href="/siswa/dashboard">Dashboard</a>
        <span aria-hidden="true">›</span>
        <a href="{{ route('siswa.leisure') }}">Zona Santai</a>
        <span aria-hidden="true">›</span>
        <a href="{{ route('siswa.fun-zone.discussions') }}">Ruang Diskusi</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">{{ \Illuminate\Support\Str::limit($discussion->title, 25) }}</span>
    </nav>

    @php
        $user = auth()->user();
        $isAuthor = $user && $user->id === $discussion->user_id;
        $isStaff = $user && in_array($user->role, ['guru', 'admin'], true);
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

    {{-- Main Topic Question Card --}}
    <article class="panel discuss-detail-card {{ $isSolved ? 'is-solved' : '' }}">
        <header class="discuss-detail-header">
            <div class="discuss-detail-meta">
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
                <span class="muted text-sm">
                    Dilihat {{ $discussion->views_count }} kali
                </span>
            </div>

            <h1 class="discuss-detail-title">{{ $discussion->title }}</h1>

            <div class="discuss-detail-author-bar">
                <div class="discuss-author-info">
                    <span class="discuss-avatar">
                        {{ mb_substr($discussion->user->name, 0, 1) }}
                    </span>
                    <div>
                        <strong>{{ $discussion->user->name }}</strong>
                        <small class="muted">{{ ucfirst($discussion->user->role) }} · Dibuat {{ $discussion->created_at->diffForHumans() }}</small>
                    </div>
                </div>

                @if($isAuthor || $isStaff)
                    <form method="post" action="{{ route('siswa.fun-zone.discussions.destroy', ['slug' => $discussion->slug]) }}" data-confirm="Apakah kamu yakin ingin menghapus diskusi ini beserta seluruh balasannya?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            Hapus Diskusi
                        </button>
                    </form>
                @endif
            </div>
        </header>

        <div class="discuss-detail-body">
            <div class="discuss-content-text">
                {!! nl2br(e($discussion->content)) !!}
            </div>

            @if($discussion->sql_snippet)
                <div class="discuss-code-box">
                    <div class="discuss-code-header">
                        <span class="discuss-code-label">Potongan SQL</span>
                        <span class="discuss-code-type">Query Siswa</span>
                    </div>
                    <pre class="discuss-sql-code"><code>{{ $discussion->sql_snippet }}</code></pre>
                </div>
            @endif
        </div>
    </article>

    {{-- Replies Section --}}
    <section class="discuss-replies-section mt-4">
        <div class="discuss-replies-heading">
            <h2>{{ $discussion->replies->count() }} Balasan</h2>
            @if($isSolved)
                <span class="text-success text-sm font-semibold">✓ Solusi telah terpilih</span>
            @endif
        </div>

        <div class="discuss-replies-list">
            @forelse($discussion->replies as $reply)
                @php
                    $isReplyAuthor = $user && $user->id === $reply->user_id;
                @endphp
                <article class="panel discuss-reply-card {{ $reply->is_solution ? 'is-marked-solution' : '' }}" id="reply-{{ $reply->id }}">
                    @if($reply->is_solution)
                        <div class="discuss-solution-banner">
                            <span>✓ Jawaban yang Membantu (Solusi Terpilih)</span>
                        </div>
                    @endif

                    <div class="discuss-reply-header">
                        <div class="discuss-author-info">
                            <span class="discuss-avatar {{ $reply->is_solution ? 'avatar-solution' : '' }}">
                                {{ mb_substr($reply->user->name, 0, 1) }}
                            </span>
                            <div>
                                <strong>{{ $reply->user->name }}</strong>
                                <small class="muted">{{ ucfirst($reply->user->role) }} · {{ $reply->created_at->diffForHumans() }}</small>
                            </div>
                        </div>

                        <div class="discuss-reply-actions">
                            {{-- Mark as Solution Button --}}
                            @if($isAuthor || $isStaff)
                                <form method="post" action="{{ route('siswa.fun-zone.discussions.solution', ['slug' => $discussion->slug, 'replyId' => $reply->id]) }}">
                                    @csrf
                                    @if($reply->is_solution)
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Batal tandai solusi">
                                            Cabut Solusi
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Tandai jawaban ini sebagai solusi yang membantu">
                                            ✓ Tandai Solusi
                                        </button>
                                    @endif
                                </form>
                            @endif

                            {{-- Delete Reply Button --}}
                            @if($isReplyAuthor || $isStaff)
                                <form method="post" action="{{ route('siswa.fun-zone.discussions.reply.destroy', ['slug' => $discussion->slug, 'replyId' => $reply->id]) }}" data-confirm="Hapus balasan ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="discuss-reply-body">
                        <div class="discuss-content-text">
                            {!! nl2br(e($reply->content)) !!}
                        </div>

                        @if($reply->sql_snippet)
                            <div class="discuss-code-box">
                                <div class="discuss-code-header">
                                    <span class="discuss-code-label">Contoh / Solusi SQL</span>
                                </div>
                                <pre class="discuss-sql-code"><code>{{ $reply->sql_snippet }}</code></pre>
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="panel text-center py-4 text-muted">
                    <p class="mb-0">Belum ada balasan pada topik ini. Jadilah yang pertama memberikan bantuan solusi!</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Reply Form Section --}}
    <section class="panel discuss-reply-form-card mt-4">
        <h3>Kirim Balasan</h3>
        <p class="muted text-sm">Bagikan pemahaman, petunjuk, atau potongan query SQL yang relevan untuk membantu teman.</p>

        <form method="post" action="{{ route('siswa.fun-zone.discussions.reply', ['slug' => $discussion->slug]) }}">
            @csrf

            <div class="mb-3">
                <label for="reply_content" class="form-label">Isi Balasan <span class="text-danger">*</span></label>
                <textarea name="content" id="reply_content" rows="4" class="form-control @error('content') is-invalid @enderror" placeholder="Tuliskan saran atau penjelasanmu..." required>{{ old('content') }}</textarea>
                @error('content')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="reply_sql_snippet" class="form-label">Potongan SQL <small class="text-muted">(Opsional)</small></label>
                <textarea name="sql_snippet" id="reply_sql_snippet" rows="3" class="form-control font-monospace discuss-sql-input @error('sql_snippet') is-invalid @enderror" placeholder="SELECT ... FROM ...">{{ old('sql_snippet') }}</textarea>
                @error('sql_snippet')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <button type="submit" class="btn btn-primary">
                    <span>Kirim Balasan</span>
                    <span aria-hidden="true">→</span>
                </button>
                <a href="{{ route('siswa.fun-zone.discussions') }}" class="btn btn-outline-secondary">
                    ← Kembali ke Daftar Diskusi
                </a>
            </div>
        </form>
    </section>
</div>
@endsection

