@extends('layout')

@section('content')
<a href="/siswa/{{ $test->type === 'pretest' ? 'pretest' : 'posttest' }}">← Daftar tes</a>
<h1 class="mt-3">{{ $test->title }}</h1>

@if($result?->submitted_at)
    @php($timeExpired = $result->ends_at && $result->submitted_at->greaterThanOrEqualTo($result->ends_at))
    <div class="panel reading">
        <div class="eyebrow">{{ $timeExpired ? 'WAKTU HABIS' : 'TES SELESAI' }}</div>
        <h2>Nilaimu: {{ $result->score }}</h2>
        <p>{{ $timeExpired ? 'Waktu berakhir dan jawaban yang tersimpan otomatis dinilai.' : 'Jawaban dikumpulkan pada '.$result->submitted_at->timezone(config('app.timezone'))->format('d M Y, H:i').'.' }}</p>
        <a class="btn btn-primary" href="/siswa/progress">Lihat Progress →</a>
    </div>
@elseif(!$result)
    <div class="panel reading">
        <h3>Siap mengukur pemahamanmu?</h3>
        <p>Tes berisi {{ $test->questions()->count() }} soal. {{ $test->duration_minutes ? 'Waktu pengerjaan '.$test->duration_minutes.' menit dan dimulai saat kamu menekan tombol mulai.' : 'Tes ini tidak memiliki batas waktu.' }} Jawaban dinilai otomatis.</p>
        <form action="/siswa/tests/{{ $test->id }}/start" method="post">@csrf<button class="btn btn-primary">Mulai Tes →</button></form>
    </div>
@else
    @if($result->ends_at)
        <div class="learning-timer" data-server-countdown data-countdown-mode="duration" data-target-time="{{ $result->ends_at->timestamp }}" data-server-now="{{ now()->timestamp }}" role="timer" aria-live="polite">
            <span class="learning-timer-icon" aria-hidden="true">◷</span><span><b>Sisa waktu <strong data-countdown-value>--:--</strong></b><small data-countdown-status>Waktu tersisa</small></span>
        </div>
    @endif
    <p class="muted">Jawaban tersimpan otomatis. Jika waktu habis, jawaban terakhir yang tersimpan akan dinilai.</p>
    <form action="/siswa/tests/{{ $test->id }}/submit" method="post" data-confirm="Kumpulkan jawaban sekarang?">@csrf
        @foreach($result->snapshot as $q)
            <fieldset class="panel mb-3"><legend class="h4">{{ $loop->iteration }}. {{ $q['question'] }}</legend>
                @foreach(['A','B','C','D'] as $option)
                    <label class="answer-option"><input type="radio" name="answers[{{ $q['id'] }}]" value="{{ $option }}" data-question-id="{{ $q['id'] }}" @checked(($savedAnswers[$q['id']] ?? old('answers.'.$q['id'])) === $option)> <b>{{ $option }}</b> {{ $q['option_'.strtolower($option)] }}</label>
                @endforeach
            </fieldset>
        @endforeach
        <button class="btn btn-primary" data-test-submit>Kumpulkan Jawaban</button>
    </form>
    <div class="visually-hidden" aria-live="polite" data-answer-save-status></div>
    <script>
    (() => {
        const form = document.querySelector('form[action$="/submit"]');
        const csrf = form?.querySelector('input[name="_token"]')?.value;
        const status = document.querySelector('[data-answer-save-status]');
        let saves = Promise.resolve();
        let finishing = false;
        const save = input => {
            saves = saves.then(async () => {
                const response = await fetch(@json(url('/siswa/tests/'.$test->id.'/answers')), {
                    method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                    body: JSON.stringify({question_id: input.dataset.questionId, answer: input.value})
                });
                if (!response.ok) throw new Error('Jawaban tidak dapat disimpan.');
                status.textContent = 'Jawaban tersimpan.';
            }).catch(() => { status.textContent = 'Gagal menyimpan jawaban. Periksa koneksi internet.'; });
        };
        form?.addEventListener('change', event => { if (event.target.matches('input[type=radio]')) save(event.target); });
        const finish = async () => {
            if (finishing || !form) return;
            finishing = true;
            await saves;
            form.querySelectorAll('input[type=radio],button').forEach(el => el.disabled = true);
            form.submit();
        };
        document.querySelector('[data-server-countdown]')?.addEventListener('countdown:expired', finish);
    })();
    </script>
    <script src="{{ asset('assets/timing.js') }}?v=20261027" defer></script>
@endif
@endsection
