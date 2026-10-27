@extends('layout')
@section('title', 'Dashboard')
@section('content')
@php($role = auth()->user()->role)
@if($role === 'siswa')
    <div class="student-dashboard-page">
        <section class="student-hero" aria-labelledby="student-greeting">
            <img class="student-hero-art" src="/assets/tebak-hasil-query.png" alt="Ruang belajar dengan laptop, buku basis data, tanaman, dan lampu meja" fetchpriority="high">
            <div class="student-hero-wash" aria-hidden="true"></div>
            <div class="student-hero-copy">
                <nav class="student-crumb" aria-label="Breadcrumb"><span>RUANG BELAJAR</span><i aria-hidden="true">/</i><span>DASHBOARD</span></nav>
                <h1 id="student-greeting">Halo, {{ auth()->user()->name }} <span aria-hidden="true">👋</span></h1>
                <p>Query kecil hari ini, kemampuan besar untuk masa depan.</p>
                <div class="student-year-pill"><span aria-hidden="true">▦</span>{{ $year?->name ?? 'Tahun ajaran belum diatur' }}@if($year)<i aria-hidden="true">·</i>Semester {{ $year->semester }}@endif</div>
                <div class="student-hero-actions">
                    <a class="student-primary-button" href="{{ $currentMaterial ? '/siswa/materials/'.$currentMaterial->id : '/siswa/materials' }}">Lanjutkan Belajar <span aria-hidden="true">→</span></a>
                    <a class="student-secondary-button" href="/siswa/lab"><span aria-hidden="true">⌘</span>Buka SQL Lab</a>
                </div>
                @if($currentMaterial)<p class="student-current-lesson">Materi berikutnya: <b>{{ $currentMaterial->title }}</b></p>@endif
            </div>
            <aside class="student-progress-card" aria-label="Progress keseluruhan {{ $progress['percent'] }} persen">
                <h2>Progress keseluruhan</h2>
                <div class="student-progress-ring" role="img" aria-label="{{ $progress['percent'] }} persen" style="--student-progress: {{ $progress['percent'] }}%"><span>{{ $progress['percent'] }}<small>%</small></span></div>
                <p>@if($progress['percent'] >= 100)Kamu luar biasa! Pertahankan semangatnya!@elseif($progress['percent'] >= 50)Kamu semakin dekat. Teruskan langkahmu!@elseif($progress['percent'] > 0)Langkah kecil hari ini, progres bertambah.@elseMulai satu materi hari ini.@endif</p>
            </aside>
        </section>

        <section class="student-next-section" aria-labelledby="next-steps-title">
            <div class="student-section-heading"><h2 id="next-steps-title">Lanjut ke langkah berikutnya <span aria-hidden="true">✦</span></h2></div>
            <div class="student-next-grid">
                <a class="student-next-card next-material" href="/siswa/materials">
                    <span class="student-next-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5.5c2.8-.9 5.5-.4 8 1.3v13c-2.5-1.7-5.2-2.2-8-1.3zM20 5.5c-2.8-.9-5.5-.4-8 1.3v13c2.5-1.7 5.2-2.2 8-1.3z"/></svg></span>
                    <span class="student-next-copy"><b>Materi</b><small>Bangun pemahaman dasar hingga lanjutan.</small></span>
                    <svg class="student-next-art art-books" viewBox="0 0 150 110" aria-hidden="true"><path d="M39 34 112 18l12 53-74 17z"/><path d="m38 50 75-14 10 18-73 16z"/><path d="m38 70 72-12 9 19-73 12z"/><path d="m51 86 70-13 8 17-70 14z"/><path d="m54 43 42-9M55 59l38-8M56 79l34-6"/></svg>
                    <span class="student-next-arrow" aria-hidden="true">→</span>
                </a>
                <a class="student-next-card next-lab" href="/siswa/lab">
                    <span class="student-next-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m8 7-5 5 5 5M16 7l5 5-5 5M14 4l-4 16"/></svg></span>
                    <span class="student-next-copy"><b>SQL Lab</b><small>Coba query pertamamu di lingkungan interaktif.</small></span>
                    <span class="student-terminal-art" aria-hidden="true"><i></i><i></i><i></i><code>SELECT *<br>FROM belajar;</code></span>
                    <span class="student-next-arrow" aria-hidden="true">→</span>
                </a>
                <a class="student-next-card next-challenge" href="/siswa/challenges">
                    <span class="student-next-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 4h8v4a4 4 0 0 1-8 0zM8 6H4v2a4 4 0 0 0 4 4m8-6h4v2a4 4 0 0 1-4 4m-4 0v5m-4 3h8m-7-3h6"/></svg></span>
                    <span class="student-next-copy"><b>Challenge</b><small>Uji kemampuanmu dengan berbagai soal.</small></span>
                    <svg class="student-next-art art-trophy" viewBox="0 0 150 110" aria-hidden="true"><path d="M49 22h52v29c0 18-11 29-26 29S49 69 49 51zM49 31H31v11c0 17 9 24 23 24m47-35h18v11c0 17-9 24-23 24m-21 8v16m-16 9h32"/><path d="m75 34 4 8 9 1-7 6 2 9-8-5-8 5 2-9-7-6 9-1z"/></svg>
                    <span class="student-next-arrow" aria-hidden="true">→</span>
                </a>
                <a class="student-next-card next-project" href="/siswa/projects">
                    <span class="student-next-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 7h7l2 2h9v10H3zM3 7V5h7l2 2"/></svg></span>
                    <span class="student-next-copy"><b>Project</b><small>Bangun solusi dari masalah dunia nyata.</small></span>
                    <svg class="student-next-art art-project" viewBox="0 0 150 110" aria-hidden="true"><path d="m44 20 60-9 10 67-61 9zM52 33l42-6m-40 17 44-7m-42 18 25-4m-16 33 37-6"/><path d="m79 50 39-8 9 43-40 8zM86 62l24-5m-21 17 26-5"/></svg>
                    <span class="student-next-arrow" aria-hidden="true">→</span>
                </a>
            </div>
        </section>

        <section class="student-dashboard-lower" aria-label="Rencana dan aktivitas belajar">
            <div class="student-dashboard-panel student-plan-panel">
                <header class="student-panel-heading"><h2><span aria-hidden="true">▦</span> Rencana pembelajaran</h2><a href="/siswa/materials">Lihat semua <span aria-hidden="true">→</span></a></header>
                @if($dashboardLessons->isEmpty())
                    <div class="student-empty-state"><span aria-hidden="true">▤</span><b>Materi belum tersedia</b><p>Materi dari guru akan muncul di sini.</p></div>
                @else
                    <div class="student-lesson-timeline">
                        @foreach($dashboardLessons as $lesson)
                            @php($material = $lesson['material'])
                            <a class="student-lesson-row status-{{ $lesson['status'] }}" href="/siswa/materials/{{ $material->id }}">
                                <span class="student-timeline-marker" aria-hidden="true">@if($lesson['status'] === 'completed')✓@else<span></span>@endif</span>
                                <span class="student-lesson-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5.5c2.8-.9 5.5-.4 8 1.3v13c-2.5-1.7-5.2-2.2-8-1.3zM20 5.5c-2.8-.9-5.5-.4-8 1.3v13c2.5-1.7 5.2-2.2 8-1.3z"/></svg></span>
                                <span class="student-lesson-copy"><small>MINGGU {{ $material->week }} · {{ $lesson['status'] === 'completed' ? 'SELESAI' : ($lesson['status'] === 'current' ? 'MINGGU INI' : 'SELANJUTNYA') }}</small><b>{{ $material->title }}</b><span>{{ \Illuminate\Support\Str::limit($material->description, 76) }}</span></span>
                                <span class="student-lesson-status">{{ $lesson['status'] === 'completed' ? 'Selesai' : ($lesson['status'] === 'current' ? 'Sedang berjalan' : 'Belum dimulai') }}</span>
                                <span class="student-lesson-arrow" aria-hidden="true">›</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="student-dashboard-panel student-activity-panel">
                <header class="student-panel-heading"><h2><span aria-hidden="true">♧</span> Aktivitas terbaru</h2><a href="/siswa/progress">Lihat semua <span aria-hidden="true">→</span></a></header>
                @forelse($logs->take(4) as $log)
                    <a class="student-activity-row" href="/siswa/progress">
                        <span class="student-activity-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12.5 9.5 17 19 7.5"/><circle cx="12" cy="12" r="9"/></svg></span>
                        <span class="student-activity-copy"><b>{{ $log->description }}</b><small>Aktivitas belajar</small></span>
                        <time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->diffForHumans() }}</time>
                        <i class="student-activity-dot" aria-hidden="true"></i>
                    </a>
                @empty
                    <div class="student-empty-state"><span aria-hidden="true">◷</span><b>Belum ada aktivitas</b><p>Mulai belajar untuk melihat progresmu di sini.</p></div>
                @endforelse
            </div>
        </section>
    </div>
@else
    @include('dashboard.staff')
@endif
@endsection
