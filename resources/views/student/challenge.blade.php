@extends('layout')

@section('content')
<a href="/siswa/challenges">← Semua challenge</a>
<h1 class="mt-3">{{ $challenge->title }}</h1>
<p><span class="badge text-bg-primary">{{ $challenge->difficulty }}</span> &nbsp; {{ $challenge->points }} XP</p>
<div class="lab-grid">
    @include('partials.schema')
    <div class="panel">
        <p class="preserve">{{ $challenge->description }}</p>
        @if($submission?->status === 'selesai')
            <div class="alert alert-success">✓ Challenge selesai. {{ $submission->score }} XP sudah tercatat.</div>
            <p class="muted">Diselesaikan pada {{ $submission->completed_at?->timezone(config('app.timezone'))->format('d M Y, H:i') }}.</p>
        @elseif($submission?->status === 'berlangsung')
            @if($submission->ends_at)
                <div class="learning-timer mb-3" data-server-countdown data-countdown-mode="duration" data-target-time="{{ $submission->ends_at->timestamp }}" data-server-now="{{ now()->timestamp }}" role="timer" aria-live="polite">
                    <span class="learning-timer-icon" aria-hidden="true">◷</span><span><b>Sisa waktu <strong data-countdown-value>--:--</strong></b><small data-countdown-status>Waktu tersisa</small></span>
                </div>
            @endif
            <p class="muted">{{ $challenge->duration_minutes ? 'Sesi dimulai '.$submission->started_at?->timezone(config('app.timezone'))->format('d M Y, H:i').'. Timer tetap berjalan saat halaman dimuat ulang.' : 'Challenge ini tidak memiliki batas waktu.' }}</p>
            <form method="post" action="/siswa/challenges/{{ $challenge->id }}">@csrf
                <label for="challenge-query" class="form-label">Query jawaban</label>
                <textarea id="challenge-query" name="query" class="form-control font-monospace" rows="8" maxlength="4000" required>{{ old('query', $submission->query ?: 'SELECT ') }}</textarea>
                <button class="btn btn-primary mt-3" data-challenge-submit>Periksa Jawaban →</button>
            </form>
        @elseif($submission?->status === 'kedaluwarsa')
            <div class="alert alert-warning">Waktu challenge berakhir. Sesi ini tidak menerima jawaban lagi.</div>
            <form method="post" action="/siswa/challenges/{{ $challenge->id }}/start">@csrf<button class="btn btn-primary">Mulai Sesi Baru →</button></form>
        @else
            <p>{{ $challenge->duration_minutes ? 'Kamu memiliki '.$challenge->duration_minutes.' menit. Timer dimulai setelah tombol ditekan.' : 'Challenge ini tidak memiliki batas waktu.' }}</p>
            <form method="post" action="/siswa/challenges/{{ $challenge->id }}/start">@csrf<button class="btn btn-primary">Mulai Challenge →</button></form>
        @endif
        <small class="muted d-block mt-3">Percobaan tersimpan: {{ $submission?->attempt ?? 0 }}. Hasil dibandingkan berdasarkan nilai kolom dan baris; urutan baris diabaikan.</small>
    </div>
</div>
@if($submission?->status === 'berlangsung' && $submission->ends_at)
    <script>
    (() => {
        const timer = document.querySelector('[data-server-countdown]');
        timer?.addEventListener('countdown:expired', async () => {
            const form = document.querySelector('form[action$="/{{ $challenge->id }}"]');
            const body = new FormData();
            const query = form?.querySelector('textarea[name="query"]')?.value;
            if (query !== undefined) body.append('query', query);
            document.querySelectorAll('#challenge-query,[data-challenge-submit]').forEach(el => el.disabled = true);
            await fetch(@json(url('/siswa/challenges/'.$challenge->id.'/expire')), {
                method: 'POST', body, headers: {'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json'}
            });
            window.location.reload();
        });
    })();
    </script>
    <script src="{{ asset('assets/timing.js') }}?v=20261027" defer></script>
@endif
@endsection
