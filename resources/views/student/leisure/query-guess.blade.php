@extends('layout')
@section('title', 'Tebak Hasil Query')
@section('content')
<nav class="leisure-breadcrumb" aria-label="Breadcrumb"><a href="/siswa/dashboard">Dashboard</a><span aria-hidden="true">›</span><a href="{{ route('siswa.leisure') }}">Zona Santai</a><span aria-hidden="true">›</span><span aria-current="page">Tebak Hasil Query</span></nav>

@if(isset($summary))
    <section class="panel guess-summary">
        <span class="leisure-kicker">PERMAINAN SELESAI</span>
        <h1>Hasil Tebak Hasil Query</h1>
        <p class="leisure-lead">Sesi ini sudah selesai. Skor game terpisah dari nilai akademik.</p>
        <div class="guess-summary-score"><strong>{{ $summary->score }}</strong><span>dari 100 poin</span></div>
        <div class="guess-score-grid"><div><b>{{ $summary->correct_count }}</b><span>Jawaban benar</span></div><div><b>{{ $summary->wrong_count }}</b><span>Jawaban salah</span></div><div><b>{{ $questionCount }}</b><span>Pertanyaan selesai</span></div><div><b>{{ $questionCount ? (int) round(100 * ($summary->correct_count + $summary->wrong_count) / $questionCount) : 0 }}%</b><span>Progres</span></div></div>
        <p class="guess-reward-note">Sistem bonus XP dan Koin Lab belum tersedia. Skor ini tidak menambah XP challenge atau nilai pre/post-test.</p>
        <form method="post" action="{{ route('siswa.fun-zone.query-guess.new') }}">@csrf<button class="btn btn-primary">Main lagi</button> <a class="btn btn-outline-secondary" href="{{ route('siswa.leisure') }}">Kembali ke Zona Santai</a></form>
    </section>
@else
    @php($percent = (int) round(100 * ($session->correct_count + $session->wrong_count) / $questionCount))
    <header class="guess-header">
        <div class="guess-title-wrap"><span class="guess-title-icon" aria-hidden="true">?</span><div><h1>Tebak Hasil Query</h1><p>Uji logikamu dengan menebak hasil dari perintah SQL. Pilih jawaban yang paling tepat!</p></div></div>
        <div class="guess-top-stats"><div><span class="guess-stat-icon score" aria-hidden="true">♜</span><span><small>Skor</small><b>{{ $session->score }}</b></span></div><div><span class="guess-stat-icon streak" aria-hidden="true">♨</span><span><small>Streak</small><b>{{ $streak }} benar</b></span></div><div><span class="guess-stat-icon level" aria-hidden="true">▥</span><span><small>Level</small><b>—</b><small>Belum tersedia</small></span></div></div>
    </header>

    <div class="guess-layout">
        <section class="guess-main panel">
            <div class="guess-question-progress"><b>Pertanyaan {{ $questionNumber }} <span>dari {{ $questionCount }}</span></b><span>{{ $percent }}%</span></div>
            <div class="progress guess-progress" role="progressbar" aria-label="Progres permainan" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:{{ $percent }}%"></div></div>

            <div class="guess-source-grid">
                @foreach($datasetTables as $table)
                    <section class="guess-data-card"><div class="guess-card-title"><span class="guess-mini-icon" aria-hidden="true">▤</span><h2>Tabel: {{ $table['name'] }}</h2></div><div class="table-responsive guess-table-wrap"><table class="table table-sm guess-table mb-0"><thead><tr>@foreach($table['columns'] as $column)<th scope="col">{{ $column }}</th>@endforeach</tr></thead><tbody>@foreach($table['rows'] as $row)<tr>@foreach($table['columns'] as $column)<td>{{ $row[$column] ?? 'NULL' }}</td>@endforeach</tr>@endforeach</tbody></table></div></section>
                @endforeach
                <section class="guess-query-card {{ count($datasetTables) > 1 ? 'span-two' : '' }}"><div class="guess-card-title"><span class="guess-mini-icon code" aria-hidden="true">&lt;/&gt;</span><h2>Query SQL</h2><button type="button" class="guess-copy" data-copy-query="{{ $question['query'] }}">▢ Salin</button></div><pre class="guess-code"><code>{!! $question['highlighted_query'] !!}</code></pre><span class="guess-copy-status" aria-live="polite"></span></section>
            </div>

            <h2 class="guess-prompt"><span aria-hidden="true">?</span>{{ $question['prompt'] }}</h2>
            @if($answer)
                <div class="guess-feedback {{ $answer['correct'] ? 'is-correct' : 'is-wrong' }}" role="status">
                    <b>{{ $answer['correct'] ? 'Jawabanmu benar!' : 'Jawabanmu belum tepat.' }}</b>
                    <p>{{ $question['explanation'] }}</p>
                    @if(!$answer['correct'])<p class="mb-0">Jawaban yang benar adalah <strong>{{ $answer['correctOption'] }}</strong>.</p>@endif
                </div>
            @endif

            <form method="post" action="{{ route('siswa.fun-zone.query-guess.answer') }}" class="guess-answers">
                @csrf
                <input type="hidden" name="question_id" value="{{ $session->question_order[$session->current_index] }}">
                @foreach($question['options'] as $label => $rows)
                    <label class="guess-option {{ $answer && $answer['choice'] === $label ? 'is-chosen' : '' }} {{ $answer && $answer['correctOption'] === $label ? 'is-answer' : '' }}">
                        <input type="radio" name="choice" value="{{ $label }}" @checked(old('choice') === $label || ($answer && $answer['choice'] === $label)) @disabled($answer) required>
                        <span class="guess-option-letter">{{ $label }}</span>
                        <span class="guess-option-table"><table class="table table-sm mb-0"><thead><tr>@foreach(array_keys($rows[0] ?? array_fill_keys($question['columns'], null)) as $column)<th>{{ $column }}</th>@endforeach</tr></thead><tbody>@forelse($rows as $row)<tr>@foreach(array_keys($rows[0]) as $column)<td>{{ $row[$column] ?? 'NULL' }}</td>@endforeach</tr>@empty<tr><td class="guess-empty-result" colspan="{{ count($question['columns']) }}">Tidak ada baris</td></tr>@endforelse</tbody></table></span>
                        @if($answer && $answer['correctOption'] === $label)<span class="guess-choice-indicator" aria-label="Jawaban benar">✓</span>@endif
                    </label>
                @endforeach
                @if(!$answer)<button type="submit" class="btn btn-primary guess-submit">Kirim Jawaban <span aria-hidden="true">➤</span></button>@endif
            </form>

            <div class="guess-controls">
                <form method="post" action="{{ route('siswa.fun-zone.query-guess.reset') }}" data-confirm="Reset permainan ini? Riwayat sesi yang belum selesai akan dihapus.">@csrf<button class="btn btn-outline-secondary">↻ Reset</button></form>
                @if($answer && $questionNumber < $questionCount)<form method="post" action="{{ route('siswa.fun-zone.query-guess.next') }}">@csrf<button class="btn btn-primary">Pertanyaan Berikutnya →</button></form>@endif
            </div>
        </section>

        <aside class="guess-sidebar">
            <details class="panel guess-side-card" open><summary><span class="guess-side-icon hint" aria-hidden="true">✦</span><b>Petunjuk</b><span class="guess-chevron" aria-hidden="true">⌄</span></summary><div class="guess-hints">@foreach($question['hints'] as $index => $hint)<details><summary>Petunjuk {{ $index + 1 }}</summary><p>{{ $hint }}</p></details>@endforeach</div></details>
            <section class="panel guess-side-card"><h2><span class="guess-side-icon history" aria-hidden="true">◷</span>Riwayat Jawaban</h2><div class="guess-history" aria-label="Status tiap pertanyaan">@foreach($history as $item)<span class="{{ $item['state'] }}" aria-label="Pertanyaan {{ $item['number'] }}: {{ ['correct' => 'benar', 'wrong' => 'salah', 'current' => 'aktif', 'pending' => 'belum dijawab'][$item['state']] }}">{{ $item['number'] }}</span>@endforeach</div><small class="guess-history-legend"><i class="correct"></i> Benar <i class="wrong"></i> Salah <i class="pending"></i> Belum dijawab</small></section>
            <section class="panel guess-side-card"><h2><span class="guess-side-icon progress" aria-hidden="true">◉</span>Skor &amp; Progres</h2><dl class="guess-score-list"><div><dt>Skor saat ini</dt><dd>{{ $session->score }}</dd></div><div><dt><i class="score-dot correct">✓</i>Pertanyaan benar</dt><dd>{{ $session->correct_count }}</dd></div><div><dt><i class="score-dot wrong">×</i>Pertanyaan salah</dt><dd>{{ $session->wrong_count }}</dd></div><div><dt>Sudah dijawab</dt><dd>{{ $session->correct_count + $session->wrong_count }}/{{ $questionCount }}</dd></div><div><dt>Progres</dt><dd>{{ $percent }}%</dd></div></dl></section>
            <section class="panel guess-side-card"><h2><span class="guess-side-icon reward" aria-hidden="true">◇</span>Hadiah</h2><p class="guess-reward-note">Sistem hadiah game belum tersedia. Skor hanya untuk sesi ini; permainan tidak memberikan XP atau Koin Lab.</p></section>
        </aside>
    </div>
    <a class="leisure-back" href="{{ route('siswa.leisure') }}">← Kembali ke Zona Santai</a>
@endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-copy-query]').forEach((button) => {
    button.addEventListener('click', async () => {
        const status = button.closest('.guess-query-card').querySelector('.guess-copy-status');
        try {
            await navigator.clipboard.writeText(button.dataset.copyQuery);
            status.textContent = 'Query disalin.';
        } catch (error) {
            status.textContent = 'Clipboard tidak tersedia di browser ini.';
        }
    });
});
</script>
@endpush
