@extends('layout')
@section('title', 'Buat Diskusi Baru · Ruang Diskusi')

@section('content')
<div class="discuss-container discuss-form-container">
    <nav class="leisure-breadcrumb" aria-label="Breadcrumb">
        <a href="/siswa/dashboard">Dashboard</a>
        <span aria-hidden="true">›</span>
        <a href="{{ route('siswa.leisure') }}">Zona Santai</a>
        <span aria-hidden="true">›</span>
        <a href="{{ route('siswa.fun-zone.discussions') }}">Ruang Diskusi</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Buat Diskusi Baru</span>
    </nav>

    <div class="panel discuss-form-card">
        <header class="discuss-form-header">
            <span class="discuss-form-icon" aria-hidden="true">✎</span>
            <div>
                <h1>Mulai Diskusi Baru</h1>
                <p class="muted">Tanyakan hal yang membingungkan atau diskusikan konsep database bersama teman.</p>
            </div>
        </header>

        <form method="post" action="{{ route('siswa.fun-zone.discussions.store') }}" class="discuss-main-form">
            @csrf

            {{-- Title --}}
            <div class="mb-3">
                <label for="title" class="form-label">
                    Judul Diskusi <span class="text-danger">*</span>
                </label>
                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="Contoh: Mengapa query LEFT JOIN menghasilkan nilai NULL pada kolom tertentu?" required maxlength="200">
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Buat judul yang spesifik agar teman sekelas mudah mengenali masalahmu.</small>
            </div>

            {{-- Category --}}
            <div class="mb-3">
                <label for="discussion_category_id" class="form-label">
                    Kategori <span class="text-danger">*</span>
                </label>
                <select name="discussion_category_id" id="discussion_category_id" class="form-select @error('discussion_category_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Kategori yang Sesuai --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('discussion_category_id') == $category->id)>
                            {{ $category->name }} &mdash; {{ $category->description }}
                        </option>
                    @endforeach
                </select>
                @error('discussion_category_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Content --}}
            <div class="mb-3">
                <label for="content" class="form-label">
                    Isi Pertanyaan / Pembahasan <span class="text-danger">*</span>
                </label>
                <textarea name="content" id="content" rows="6" class="form-control @error('content') is-invalid @enderror" placeholder="Jelaskan pertanyaan, konteks tugas, atau pemikiranmu secara jelas..." required>{{ old('content') }}</textarea>
                @error('content')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Optional SQL Snippet --}}
            <div class="mb-4">
                <label for="sql_snippet" class="form-label">
                    Potongan Kode SQL <small class="text-muted">(Opsional)</small>
                </label>
                <textarea name="sql_snippet" id="sql_snippet" rows="4" class="form-control font-monospace discuss-sql-input @error('sql_snippet') is-invalid @enderror" placeholder="SELECT nama, kelas FROM siswa WHERE nilai >= 85;">{{ old('sql_snippet') }}</textarea>
                @error('sql_snippet')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Tempelkan query yang sedang kamu uji coba untuk membantu teman melihat masalah secara langsung.</small>
            </div>

            <div class="discuss-form-actions">
                <button type="submit" class="btn btn-primary">
                    <span>Publikasikan Diskusi</span>
                    <span aria-hidden="true">→</span>
                </button>
                <a href="{{ route('siswa.fun-zone.discussions') }}" class="btn btn-outline-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

