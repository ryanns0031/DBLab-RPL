@extends('layout')
@section('title', $material->title)
@section('content')
<nav class="material-breadcrumb" aria-label="Breadcrumb">
    <a href="/siswa/dashboard">Dashboard</a><span aria-hidden="true">›</span>
    <a href="/siswa/materials">Materi</a><span aria-hidden="true">›</span>
    <span aria-current="page">{{ $material->title }}</span>
</nav>

<article class="reading panel mt-4">
    <div class="eyebrow">MINGGU {{ $material->week }}</div>
    <h1>{{ $material->title }}</h1>
    <p class="lead">{{ $material->description }}</p>

    @if($videoEmbedUrl)
        <section class="youtube-video-section" aria-label="Video pembelajaran">
            <div class="youtube-video">
                <iframe src="{{ $videoEmbedUrl }}"
                        title="Video pembelajaran: {{ $material->title }}"
                        loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
            </div>
            <p class="youtube-video-fallback">Jika video tidak dapat diputar karena pembatasan pemilik video atau YouTube,
                <a href="{{ $material->youtube_url }}" target="_blank" rel="noopener noreferrer">buka video di YouTube</a>.
            </p>
        </section>
    @endif

    <hr>
    <div class="preserve reading-content">{{ $material->content }}</div>
    <div class="d-flex gap-3 flex-wrap mt-4">
        <form method="post" action="/siswa/materials/{{ $material->id }}/complete">
            @csrf
            <button class="btn btn-primary">Tandai Sudah Dipelajari</button>
        </form>
        @if($material->file_path)
            <a class="btn btn-outline-primary" href="/materials/{{ $material->id }}/download">Unduh Materi</a>
        @endif
        <a class="btn btn-outline-primary" href="/siswa/lab">Coba di SQL Lab</a>
        <a class="btn btn-outline-primary" href="/siswa/materials">Kembali ke Materi</a>
    </div>
</article>
@endsection
