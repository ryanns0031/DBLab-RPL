@extends('layout')

@section('content')
<a href="/siswa/projects">â† Semua project</a>
<h1 class="mt-3">{{ $project->title }}</h1>
<p class="preserve">{{ $project->description }}</p>
<p class="muted">Status pengumpulan: {{ $submission ? ($submission->is_late ? 'Terlambat' : 'Sudah dikumpulkan') : 'Belum dikumpulkan' }}</p>
<p class="muted">Minggu {{ $project->week }} Â· Deadline {{ $deadlineAt->timezone(config('app.timezone'))->format('d M Y, H:i') }} ({{ config('app.timezone') }})</p>

@if(now()->greaterThanOrEqualTo($deadlineAt))
    @if($project->allow_late_submissions)
        <div class="alert alert-warning">Deadline terlewati. Pengumpulan terlambat masih diterima dan akan ditandai terlambat.</div>
    @else
        <div class="alert alert-danger">Deadline telah berakhir. Guru menutup pengumpulan setelah batas waktu.</div>
    @endif
@elseif(!$submission)
    <div class="learning-timer learning-deadline-timer" data-server-countdown data-countdown-mode="deadline" data-target-time="{{ $deadlineAt->timestamp }}" data-server-now="{{ now()->timestamp }}" role="timer" aria-live="polite">
        <span class="learning-timer-icon" aria-hidden="true">â—·</span><span><b>Deadline <strong data-countdown-value>--</strong></b><small data-countdown-status>Waktu menuju deadline</small></span>
    </div>
    <script src="{{ asset('assets/timing.js') }}?v=20261027" defer></script>
@endif

<div class="dashboard-columns">
    <section class="panel">
        <h3>Langkah demi langkah</h3>
        @foreach($project->steps as $step)
            <form action="/siswa/steps/{{ $step->id }}" method="post" class="project-step">@csrf
                <input type="hidden" name="complete" value="{{ in_array($step->id, $completed) ? 0 : 1 }}">
                <button class="step-check {{ in_array($step->id, $completed) ? 'done' : '' }}" aria-label="Ubah status {{ $step->title }}">{{ in_array($step->id, $completed) ? 'âœ“' : 'â—‹' }}</button>
                <span>{{ $step->title }}</span><small>{{ in_array($step->id, $completed) ? 'Selesai' : 'Belum selesai' }}</small>
            </form>
        @endforeach
        <small class="muted">Checklist mencatat progress mandiri. Guru menilai hasil yang kamu kumpulkan.</small>
    </section>
    <section class="panel">
        <h3>Kumpulkan hasil karyamu</h3>
        @if($submission)
            <div class="alert alert-info">
                Status: {{ $submission->is_late ? 'Terlambat' : 'Sudah dikumpulkan' }}<br>
                Terakhir dikumpulkan: {{ $submission->updated_at->timezone(config('app.timezone'))->format('d M Y, H:i') }}
                @if($submission->is_late)<br><strong>Pengumpulan terlambat</strong>@endif
                <br>Nilai: {{ $submission->score ?? 'Belum dinilai' }}
                @if($submission->feedback)<br>{{ $submission->feedback }}@endif
                @if($submission->original_name)<br>Lampiran: {{ $submission->original_name }}@endif
            </div>
        @endif
        @if($canSubmitProject)
            <form method="post" action="/siswa/projects/{{ $project->id }}" enctype="multipart/form-data">@csrf
                <label for="notes" class="form-label">Catatan project / tautan repository</label>
                <textarea id="notes" name="notes" rows="5" class="form-control" required>{{ old('notes', $submission?->notes) }}</textarea>
                <label for="project-file" class="form-label mt-3">Lampiran PDF / ZIP / TXT (maks. 10 MB)</label>
                <input id="project-file" type="file" name="file" class="form-control" accept=".pdf,.zip,.txt">
                <button class="btn btn-primary mt-3">{{ $submission ? 'Perbarui Pengumpulan' : 'Kumpulkan Project' }}</button>
            </form>
        @else
            <p>Pengumpulan untuk project ini sudah ditutup.</p>
        @endif
    </section>
</div>
@endsection

