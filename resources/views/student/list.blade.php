@extends('layout')
@section('title', match($kind) {
    'materials' => 'Materi Pembelajaran',
    'challenges' => 'Challenge',
    'projects' => 'Project',
    'pretest' => 'Pre-test',
    default => 'Post-test',
})
@section('content')
@php($title = match($kind) {
    'materials' => 'Materi Pembelajaran',
    'challenges' => 'Challenge',
    'projects' => 'Project',
    'pretest' => 'Pre-test',
    default => 'Post-test',
})
<nav class="student-crumb" aria-label="Breadcrumb">
    <a href="/siswa/dashboard">Dashboard</a><span aria-hidden="true">/</span><span aria-current="page">{{ $title }}</span>
</nav>
<header class="student-list-heading">
    <div class="eyebrow">EKSPLORASI BASIS DATA</div>
    <h1>{{ $title }}</h1>
    <p class="muted">Setiap langkah membawa kamu lebih dekat untuk menguasai Basis Data.</p>
</header>
<div class="content-grid student-content-grid">
    @forelse($rows as $row)
        <article class="panel content-card student-resource-card">
            <div class="section-title">
                <span class="quick-icon" aria-hidden="true">{{ $kind === 'materials' ? '▤' : ($kind === 'challenges' ? 'ϟ' : ($kind === 'projects' ? '◇' : '▣')) }}</span>
                @if(in_array($kind, ['pretest', 'posttest']))
                    <span class="badge text-bg-light">{{ $row->questions_count }} soal</span>
                @elseif($kind === 'challenges')
                    <span class="badge text-bg-light">{{ $row->difficulty }}</span>
                @elseif($kind === 'materials')
                    <span class="badge text-bg-light">Minggu {{ $row->week }}</span>
                @else
                    <span class="badge text-bg-light">Project aktif</span>
                @endif
            </div>
            <h2>{{ $row->title }}</h2>
            <p>{{ $row->description ?: 'Ukur pemahamanmu dengan mengerjakan tes ini.' }}</p>
            @if($kind === 'projects' && $row->deadline)
                <small class="muted">Batas pengumpulan: {{ \Illuminate\Support\Carbon::parse($row->deadline)->translatedFormat('d F Y') }}</small>
            @endif
            <a class="btn btn-outline-primary mt-3" href="/siswa/{{ in_array($kind, ['pretest', 'posttest']) ? 'tests' : $kind }}/{{ $row->id }}">
                {{ $kind === 'materials' ? 'Baca Materi' : (in_array($kind, ['pretest', 'posttest']) ? 'Mulai Tes' : ($kind === 'challenges' ? 'Mulai Challenge' : 'Lihat Project')) }} <span aria-hidden="true">→</span>
            </a>
            @if($kind === 'materials' && $row->file_path)
                <a class="student-download-link" href="/materials/{{ $row->id }}/download">↓ Unduh Materi</a>
            @endif
        </article>
    @empty
        <div class="panel empty student-list-empty">
            <span class="student-empty-mark" aria-hidden="true">{{ $kind === 'materials' ? '▤' : '✦' }}</span>
            <h2>Belum ada {{ strtolower($title) }} aktif</h2>
            <p>Konten dari guru akan muncul di halaman ini jika sudah tersedia untuk kelasmu.</p>
        </div>
    @endforelse
</div>
@endsection
