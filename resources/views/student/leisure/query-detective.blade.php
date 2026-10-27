@extends('layout')
@section('title', 'Query Detective')
@section('content')
<nav class="leisure-breadcrumb" aria-label="Breadcrumb"><a href="/siswa/dashboard">Dashboard</a><span aria-hidden="true">›</span><a href="{{ route('siswa.leisure') }}">Zona Santai</a><span aria-hidden="true">›</span><span aria-current="page">Query Detective</span></nav>

@if(isset($summary))
    <section class="panel detective-summary">
        <span class="leisure-kicker">SEMUA KASUS SELESAI</span>
        <h1>Hasil Query Detective</h1>
        <p class="leisure-lead">Kamu berhasil memeriksa seluruh kumpulan kasus. Skor permainan terpisah dari nilai akademik.</p>
        <div class="detective-final-score"><strong>{{ $summary->score }}</strong><span>dari 100 poin</span></div>
        <div class="detective-summary-stats"><div><b>{{ $summary->solved_count }}</b><span>Kasus selesai</span></div><div><b>{{ $caseCount }}</b><span>Total kasus</span></div><div><b>{{ $caseCount ? (int) round(100 * $summary->solved_count / $caseCount) : 0 }}%</b><span>Progres</span></div></div>
        <p class="detective-reward-note">Sistem XP bonus dan Koin Lab belum tersedia. Skor ini tidak menambah XP challenge atau nilai pre/post-test.</p>
        <form method="post" action="{{ route('siswa.fun-zone.query-detective.new') }}">@csrf<button class="btn btn-primary">Main lagi</button> <a class="btn btn-outline-secondary" href="{{ route('siswa.leisure') }}">Kembali ke Zona Santai</a></form>
    </section>
@else
    <header class="detective-header">
        <div class="detective-heading"><span class="detective-emblem" aria-hidden="true">⌕</span><div><h1>Query Detective</h1><p>Temukan kesalahan pada query dan perbaiki agar menghasilkan data yang benar.</p></div></div>
        <div class="detective-top-stats"><div><span class="detective-stat-mark score" aria-hidden="true">♜</span><span><small>Skor</small><b>{{ $session->score }}</b></span></div><div><span class="detective-stat-mark cases" aria-hidden="true">✓</span><span><small>Kasus selesai</small><b>{{ $session->solved_count }}</b></span></div><div><span class="detective-stat-mark level" aria-hidden="true">▥</span><span><small>Level</small><b>—</b><small>Belum tersedia</small></span></div></div>
    </header>

    <div class="detective-layout">
        <main class="detective-main-column">
            <section class="panel detective-case-progress"><div><b>Kasus {{ $caseNumber }} <span>dari {{ $caseCount }}</span></b><span>{{ $progressPercent }}%</span></div><div class="detective-track" role="progressbar" aria-label="Progres Query Detective" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100"><span style="width:{{ $progressPercent }}%"></span></div><div class="detective-case-dots" aria-label="Riwayat kasus">@foreach($history as $item)<span class="{{ $item['state'] }}" aria-label="Kasus {{ $item['number'] }}: {{ ['solved' => 'selesai', 'current' => 'sedang dikerjakan', 'pending' => 'belum dikerjakan'][$item['state']] }}">{{ $item['number'] }}</span>@endforeach</div></section>

            <section class="panel detective-case-card">
                <div class="detective-case-title"><span class="detective-database-icon" aria-hidden="true">▤</span><div><span class="detective-case-topic">{{ $case['sqlTopic'] }} · {{ $case['difficulty'] }}</span><h2>{{ $case['title'] }}</h2></div></div>
                <p class="detective-description">{{ $case['description'] }}</p>
                <div class="detective-workspace">
                    @foreach($case['dataset'] as $tableName => $rows)
                        <section class="detective-data-card"><div class="detective-card-heading"><span class="detective-small-mark" aria-hidden="true">▤</span><h3>Tabel: {{ $tableName }}</h3></div><div class="table-responsive"><table class="table table-sm detective-table mb-0"><thead><tr>@foreach($case['columns'][$tableName] as $column)<th scope="col">{{ $column }}</th>@endforeach</tr></thead><tbody>@foreach($rows as $row)<tr>@foreach($case['columns'][$tableName] as $column)<td>{{ $row[$column] ?? 'NULL' }}</td>@endforeach</tr>@endforeach</tbody></table></div></section>
                    @endforeach
                    <section class="detective-query-card"><div class="detective-card-heading"><span class="detective-small-mark code" aria-hidden="true">&lt;/&gt;</span><h3>Query yang perlu diperbaiki</h3><button class="detective-copy" type="button" data-copy-query="{{ $case['query'] }}">▢ Salin</button></div><pre class="detective-code"><code>@foreach($case['lines'] as $line)<span class="detective-line-number">{{ $loop->iteration }}</span>@foreach($line as $token)@if($token['id'])<button type="button" class="detective-token" data-token-id="{{ $token['id'] }}" aria-pressed="{{ in_array($token['id'], $caseState['selected'] ?? [], true) ? 'true' : 'false' }}">{{ $token['text'] }}</button>@else{{ $token['text'] }}@endif@endforeach
@endforeach @endforeach</code></pre><span class="detective-copy-status" aria-live="polite"></span></section>
                </div>
            </section>

            @if($solution)
                <section class="panel detective-solution" role="status">
                    <div class="detective-solution-title"><span aria-hidden="true">✓</span><h2>Penjelasan (setelah jawaban)</h2><small>Kasus selesai</small></div>
                    <div class="detective-solution-grid">
                        <div><h3>Query yang benar</h3><pre class="detective-code corrected"><code>{{ $solution['correctedQuery'] }}</code></pre></div>
                        <div class="detective-corrections"><h3>Kesalahan pada query:</h3>@foreach($solution['corrections'] as $index => $correction)<p><span class="detective-error-number">{{ $index + 1 }}</span><span><code>{{ $correction['wrong'] }}</code><span aria-hidden="true"> → </span><code>{{ $correction['correct'] }}</code><small>{{ $solution['explanation'] }}</small></span></p>@endforeach</div>
                        <div class="detective-result"><h3>Hasil akhir</h3><div class="table-responsive"><table class="table table-sm detective-table mb-0"><thead><tr>@foreach(array_keys($solution['results'][0] ?? []) as $column)<th>{{ $column }}</th>@endforeach</tr></thead><tbody>@foreach($solution['results'] as $row)<tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>@endforeach</tbody></table></div></div>
                    </div>
                    <div class="detective-result-summary"><span aria-hidden="true">💡</span><div><b>Hasil query yang benar:</b><p>{{ $solution['resultSummary'] }}</p></div></div>
                </section>
            @endif

            <div class="detective-navigation">
                @if($caseNumber > 1)<form method="post" action="{{ route('siswa.fun-zone.query-detective.previous') }}">@csrf<button class="btn btn-outline-secondary">← Kasus Sebelumnya</button></form>@else<a class="btn btn-outline-secondary" href="{{ route('siswa.leisure') }}">← Kembali ke Zona Santai</a>@endif
                @if($caseState['solved'] ?? false)<form method="post" action="{{ route('siswa.fun-zone.query-detective.next') }}">@csrf<button class="btn btn-primary">Lanjut ke Kasus Berikutnya →</button></form>@endif
                <form method="post" action="{{ route('siswa.fun-zone.query-detective.reset') }}" data-confirm="Reset permainan? Progres sesi yang belum selesai akan dihapus.">@csrf<button class="btn btn-sm btn-outline-secondary">Reset</button></form>
            </div>
        </main>

        <aside class="detective-sidebar">
            <details class="panel detective-side-card" open><summary><span class="detective-side-icon hint" aria-hidden="true">✦</span><b>Petunjuk</b><span class="detective-chevron" aria-hidden="true">⌄</span></summary><div class="detective-hints">@foreach($case['hints'] as $index => $hint)<details><summary><span>{{ $index + 1 }}</span> Petunjuk {{ $index + 1 }}</summary><p>{{ $hint }}</p></details>@endforeach</div></details>
            <section class="panel detective-side-card"><h2><span class="detective-side-icon inspect" aria-hidden="true">⌕</span>Periksa Bagian yang Salah</h2><p class="detective-instruction">Klik bagian query yang menurutmu salah. Kamu bisa memilih lebih dari satu bagian.</p>
                <form method="post" action="{{ route('siswa.fun-zone.query-detective.check') }}" class="detective-check-form" data-case-id="{{ $caseId }}">
                    @csrf<input type="hidden" name="case_id" value="{{ $caseId }}"><div class="detective-token-bank">@foreach($case['tokens'] as $token)<button type="button" class="detective-chip" data-token-id="{{ $token['id'] }}" aria-pressed="{{ in_array($token['id'], $caseState['selected'] ?? [], true) ? 'true' : 'false' }}">{{ $token['text'] }}</button>@endforeach</div><p class="detective-selection-status" aria-live="polite"></p>
                    @if(!($caseState['solved'] ?? false))<button type="submit" class="btn btn-primary detective-submit">Periksa Jawaban <span aria-hidden="true">➤</span></button>@endif
                </form>
            </section>
            <section class="panel detective-side-card"><h2><span class="detective-side-icon history" aria-hidden="true">◷</span>Riwayat Kasus</h2><div class="detective-history">@foreach($history as $item)<span class="{{ $item['state'] }}" aria-label="Kasus {{ $item['number'] }}: {{ ['solved' => 'berhasil diselesaikan', 'current' => 'sedang dikerjakan', 'pending' => 'belum dikerjakan'][$item['state']] }}">{{ $item['number'] }}</span>@endforeach</div><small class="detective-legend"><i class="solved"></i> Selesai <i class="current"></i> Aktif <i class="pending"></i> Belum dikerjakan</small></section>
            <section class="panel detective-side-card"><h2><span class="detective-side-icon score" aria-hidden="true">◉</span>Skor &amp; Progres</h2><dl class="detective-stats-list"><div><dt>Skor saat ini</dt><dd>{{ $session->score }}</dd></div><div><dt>Kasus berhasil</dt><dd>{{ $session->solved_count }}/{{ $caseCount }}</dd></div><div><dt>Progres</dt><dd>{{ $progressPercent }}%</dd></div><div><dt>Percobaan kasus ini</dt><dd>{{ $caseState['attempts'] ?? 0 }}</dd></div></dl></section>
            <section class="panel detective-side-card"><h2><span class="detective-side-icon reward" aria-hidden="true">◇</span>Hadiah</h2><p class="detective-reward-note">Sistem bonus XP dan Koin Lab belum tersedia. Skor hanya untuk sesi permainan ini dan tidak memengaruhi nilai akademik.</p></section>
        </aside>
    </div>
    <a class="leisure-back" href="{{ route('siswa.leisure') }}">← Kembali ke Zona Santai</a>
@endif
@endsection

@if(!isset($summary))
@push('scripts')
<script>
(() => {
    const form = document.querySelector('.detective-check-form');
    if (!form) return;
    const selected = new Set(@json($caseState['selected'] ?? []));
    const status = form.querySelector('.detective-selection-status');
    const sync = () => {
        document.querySelectorAll('.detective-token,.detective-chip').forEach((button) => {
            const active = selected.has(button.dataset.tokenId);
            button.classList.toggle('is-selected', active);
            button.setAttribute('aria-pressed', String(active));
        });
        status.textContent = selected.size ? `${selected.size} bagian dipilih` : 'Belum ada bagian yang dipilih';
    };
    document.querySelectorAll('.detective-token,.detective-chip').forEach((button) => button.addEventListener('click', () => {
        const id = button.dataset.tokenId;
        selected.has(id) ? selected.delete(id) : selected.add(id);
        sync();
    }));
    sync();
    form.addEventListener('submit', (event) => {
        if (!selected.size) {
            event.preventDefault();
            status.textContent = 'Pilih setidaknya satu bagian query untuk diperiksa.';
            return;
        }
        form.querySelectorAll('input[name="tokens[]"]').forEach((input) => input.remove());
        selected.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'tokens[]'; input.value = id;
            form.append(input);
        });
    });
    document.querySelectorAll('[data-copy-query]').forEach((button) => button.addEventListener('click', async () => {
        const copyStatus = button.closest('.detective-query-card').querySelector('.detective-copy-status');
        try { await navigator.clipboard.writeText(button.dataset.copyQuery); copyStatus.textContent = 'Query disalin.'; }
        catch (error) { copyStatus.textContent = 'Clipboard tidak tersedia di browser ini.'; }
    }));
})();
</script>
@endpush
@endif
