@if($role === 'guru')
    <div class="staff-dashboard">
        <section class="staff-hero staff-hero-teacher">
            <img src="/assets/tebak-hasil-query.png" alt="Ilustrasi ruang belajar SQL" aria-hidden="true">
            <div class="staff-hero-wash" aria-hidden="true"></div>
            <div class="staff-hero-copy">
                <div class="eyebrow">RUANG GURU · {{ now()->locale('id')->translatedFormat('l, d F Y') }}</div>
                <h1>Halo, {{ auth()->user()->name }}</h1>
                <p>Kelola pembelajaran, pantau perkembangan kelas, dan bantu siswa menyelesaikan tantangan berikutnya.</p>
                <div class="staff-hero-actions">
                    <a class="staff-primary-action" href="/guru/materials#editor">＋ Kelola Materi</a>
                    <a class="staff-secondary-action" href="/guru/tests#editor">Buat Evaluasi <span aria-hidden="true">→</span></a>
                </div>
                @if($year)<small class="staff-year-note">Tahun ajaran {{ $year->name }} · Semester {{ $year->semester }}</small>@endif
            </div>
        </section>

        <section class="staff-stat-grid" aria-label="Statistik pembelajaran">
            <article class="staff-stat-card tone-blue"><span class="staff-stat-icon">♙</span><div><small>Siswa</small><strong>{{ $students->count() }}</strong><span>di kelas yang Anda ampu</span></div></article>
            <article class="staff-stat-card tone-violet"><span class="staff-stat-icon">▤</span><div><small>Materi aktif</small><strong>{{ $materials->count() }}</strong><span>siap dipelajari siswa</span></div></article>
            <article class="staff-stat-card tone-amber"><span class="staff-stat-icon">☑</span><div><small>Evaluasi aktif</small><strong>{{ $evaluationTestsCount }}</strong><span>{{ $evaluationCount }} pengumpulan tercatat</span></div></article>
            <article class="staff-stat-card tone-green"><span class="staff-stat-icon">◷</span><div><small>Progress kelas</small><strong>{{ $classProgress }}%</strong><span>rata-rata progress siswa</span></div></article>
        </section>

        <section class="staff-quick-grid" aria-label="Akses cepat">
            <a href="/guru/students" class="tone-blue"><span>♙</span><b>Daftar Siswa</b><small>Lihat progres dan hasil belajar</small><i>→</i></a>
            <a href="/guru/projects#editor" class="tone-green"><span>◇</span><b>Kelola Project</b><small>Atur instruksi dan tenggat</small><i>→</i></a>
            <a href="/guru/analytics" class="tone-violet"><span>◷</span><b>Analitik Kelas</b><small>Tinjau hasil pembelajaran</small><i>→</i></a>
            <a href="/guru/challenges#editor" class="tone-amber"><span>ϟ</span><b>Kelola Challenge</b><small>Tambah latihan SQL</small><i>→</i></a>
        </section>

        <section class="staff-dashboard-grid">
            <article class="staff-panel staff-classes-panel">
                <header class="staff-panel-heading"><div><small>KELAS YANG DIAJAR</small><h2>Ruang belajar</h2></div><a href="/guru/students">Lihat siswa <span aria-hidden="true">→</span></a></header>
                @forelse($classes as $class)
                    <a class="staff-class-row" href="/guru/students?class_id={{ $class->id }}"><span class="staff-class-mark">{{ mb_substr($class->name, 0, 1) }}</span><span><b>{{ $class->name }}</b><small>{{ $class->level }} · Tahun ajaran {{ $class->academicYear?->name ?? 'belum diatur' }}</small></span><strong>{{ $class->students_count }}<small>siswa</small></strong></a>
                @empty
                    <div class="staff-empty"><b>Belum ada kelas yang ditugaskan</b><p>Hubungi administrator untuk mengatur kelas pengampu.</p></div>
                @endforelse
            </article>

            <article class="staff-panel staff-progress-panel">
                <header class="staff-panel-heading"><div><small>HASIL EVALUASI</small><h2>Ringkasan kelas</h2></div><a href="/guru/analytics">Analitik <span aria-hidden="true">→</span></a></header>
                <div class="staff-average-score"><strong>{{ $evaluationAverage === null ? '—' : number_format($evaluationAverage, 1, ',', '.') }}</strong><span>rata-rata nilai tes terkumpul</span></div>
                <div class="staff-progress-track" role="progressbar" aria-label="Progress kelas" aria-valuenow="{{ $classProgress }}" aria-valuemin="0" aria-valuemax="100"><span style="width:{{ $classProgress }}%"></span></div>
                <div class="staff-progress-caption"><span>Progress pembelajaran kelas</span><b>{{ $classProgress }}%</b></div>
                <div class="staff-evaluation-count"><span>Pre-test selesai</span><b>{{ $preDone }}</b><span>Post-test selesai</span><b>{{ $postDone }}</b></div>
            </article>

            <article class="staff-panel">
                <header class="staff-panel-heading"><div><small>TERBARU</small><h2>Materi pembelajaran</h2></div><a href="/guru/materials">Semua materi <span aria-hidden="true">→</span></a></header>
                @forelse($materials->take(4) as $material)
                    <a class="staff-material-row" href="/guru/materials?edit={{ $material->id }}#editor"><span>{{ str_pad($material->week, 2, '0', STR_PAD_LEFT) }}</span><div><b>{{ $material->title }}</b><small>{{ $classes->firstWhere('id', $material->class_id)?->name }} · {{ $material->status }}</small></div><i aria-hidden="true">→</i></a>
                @empty
                    <div class="staff-empty"><b>Belum ada materi</b><p>Tambahkan materi pertama untuk kelasmu.</p></div>
                @endforelse
            </article>

            <article class="staff-panel">
                <header class="staff-panel-heading"><div><small>AKTIVITAS SISWA</small><h2>Terbaru</h2></div><a href="/guru/students">Pantau kelas <span aria-hidden="true">→</span></a></header>
                @forelse($logs->take(5) as $log)
                    <div class="staff-activity-row"><span class="staff-activity-dot"></span><div><b>{{ $log->description }}</b><small>{{ $log->user?->name ?? 'Pengguna' }} · {{ $log->created_at->diffForHumans() }}</small></div></div>
                @empty
                    <div class="staff-empty"><b>Belum ada aktivitas</b><p>Aktivitas belajar siswa akan tercatat di sini.</p></div>
                @endforelse
            </article>

            <article class="staff-panel staff-wide-panel">
                <header class="staff-panel-heading"><div><small>PENGUMPULAN SISWA</small><h2>Project terbaru</h2></div><a href="/guru/projects">Kelola project <span aria-hidden="true">→</span></a></header>
                @forelse($recentProjectSubmissions as $submission)
                    <a class="staff-submission-row" href="/guru/projects/{{ $submission->project_id }}/submissions"><span class="staff-submission-icon">◇</span><span><b>{{ $submission->student?->name ?? 'Siswa' }}</b><small>{{ $submission->project?->title }} · {{ $submission->updated_at->diffForHumans() }}</small></span><strong>{{ $submission->score === null ? 'Perlu dinilai' : 'Nilai '.$submission->score }}</strong></a>
                @empty
                    <div class="staff-empty"><b>Belum ada pengumpulan project</b><p>Pengumpulan siswa akan tampil setelah dikirimkan.</p></div>
                @endforelse
            </article>
        </section>
    </div>
@else
    <div class="staff-dashboard">
        <section class="staff-hero staff-hero-admin">
            <img src="/assets/tebak-hasil-query.png" alt="Ilustrasi basis data dan ruang belajar" aria-hidden="true">
            <div class="staff-hero-wash" aria-hidden="true"></div>
            <div class="staff-hero-copy">
                <div class="eyebrow">PUSAT ADMINISTRASI · {{ now()->locale('id')->translatedFormat('l, d F Y') }}</div>
                <h1>Selamat datang, {{ auth()->user()->name }}</h1>
                <p>Kelola akun, kelas, dan aktivitas DBLab-RPL dari satu tempat.</p>
                <div class="staff-hero-actions"><a class="staff-primary-action" href="/admin/users?role=siswa#editor">＋ Tambah Siswa</a><a class="staff-secondary-action" href="/admin/import">Import Data <span aria-hidden="true">→</span></a></div>
                @if($year)<small class="staff-year-note">Tahun ajaran aktif: {{ $year->name }} · Semester {{ $year->semester }}</small>@endif
            </div>
        </section>

        <section class="staff-stat-grid staff-admin-stats" aria-label="Statistik platform">
            @foreach([['Pengguna', $counts['Pengguna'], 'Seluruh akun terdaftar', 'tone-blue', '♙'], ['Siswa', $counts['Siswa'], 'Akun siswa', 'tone-violet', '♧'], ['Guru', $counts['Guru'], 'Akun guru', 'tone-amber', '♙'], ['Materi aktif', $counts['Materi aktif'], 'Konten pembelajaran', 'tone-green', '▤']] as [$label, $value, $hint, $tone, $icon])
                <article class="staff-stat-card {{ $tone }}"><span class="staff-stat-icon">{{ $icon }}</span><div><small>{{ $label }}</small><strong>{{ number_format($value) }}</strong><span>{{ $hint }}</span></div></article>
            @endforeach
        </section>

        <section class="staff-quick-grid staff-admin-quick" aria-label="Akses cepat administrasi">
            <a href="/admin/users?role=guru#editor" class="tone-blue"><span>♙</span><b>Tambah Guru</b><small>Daftarkan akun pengajar</small><i>→</i></a>
            <a href="/admin/classes#editor" class="tone-green"><span>▦</span><b>Kelola Kelas</b><small>Atur kelas dan tahun ajaran</small><i>→</i></a>
            <a href="/admin/assignments" class="tone-violet"><span>♧</span><b>Guru Pengampu</b><small>Hubungkan guru dan kelas</small><i>→</i></a>
            <a href="/review/pameran-karya" class="tone-amber"><span>▧</span><b>Tinjau Karya</b><small>Moderasi pameran siswa</small><i>→</i></a>
        </section>

        <section class="staff-dashboard-grid staff-admin-grid">
            <article class="staff-panel staff-wide-panel">
                <header class="staff-panel-heading"><div><small>PENGGUNAAN PLATFORM</small><h2>Aktivitas 7 hari terakhir</h2></div><a href="/admin/logs">Log sistem <span aria-hidden="true">→</span></a></header>
                <div class="staff-chart-wrap"><canvas id="admin-activity-chart" aria-label="Grafik jumlah aktivitas per hari"></canvas></div>
            </article>
            <article class="staff-panel">
                <header class="staff-panel-heading"><div><small>AKUN TERBARU</small><h2>Pengguna baru</h2></div><a href="/admin/users">Kelola pengguna <span aria-hidden="true">→</span></a></header>
                @forelse($recentUsers as $user)
                    <a class="staff-user-row" href="/admin/users?role={{ $user->role }}"><span class="staff-avatar-mark">{{ mb_substr($user->name, 0, 1) }}</span><span><b>{{ $user->name }}</b><small>{{ $user->email }}</small></span><i class="staff-role-badge role-{{ $user->role }}">{{ ucfirst($user->role) }}</i></a>
                @empty
                    <div class="staff-empty"><b>Belum ada pengguna</b><p>Pengguna terbaru akan tampil di sini.</p></div>
                @endforelse
            </article>
            <article class="staff-panel">
                <header class="staff-panel-heading"><div><small>KONTEN TERBARU</small><h2>Materi pembelajaran</h2></div><a href="/admin/classes">Daftar kelas <span aria-hidden="true">→</span></a></header>
                @forelse($recentContent as $material)
                    <div class="staff-material-row"><span>{{ str_pad($material->week, 2, '0', STR_PAD_LEFT) }}</span><div><b>{{ $material->title }}</b><small>{{ $material->schoolClass?->name ?? 'Kelas tidak tersedia' }} · {{ $material->status }}</small></div></div>
                @empty
                    <div class="staff-empty"><b>Belum ada materi</b><p>Materi yang dibuat guru akan tampil di sini.</p></div>
                @endforelse
            </article>
            <article class="staff-panel">
                <header class="staff-panel-heading"><div><small>AKTIVITAS ADMINISTRASI</small><h2>Log terbaru</h2></div><a href="/admin/logs">Semua aktivitas <span aria-hidden="true">→</span></a></header>
                @forelse($logs->take(5) as $log)
                    <div class="staff-activity-row"><span class="staff-activity-dot"></span><div><b>{{ $log->description }}</b><small>{{ $log->user?->name ?? 'Sistem' }} · {{ $log->created_at->diffForHumans() }}</small></div></div>
                @empty
                    <div class="staff-empty"><b>Belum ada aktivitas</b><p>Log sistem akan muncul setelah ada aktivitas.</p></div>
                @endforelse
            </article>
        </section>
    </div>
    @push('scripts')
        <script src="/assets/chart.umd.js"></script>
        <script>
            const activityCanvas = document.getElementById('admin-activity-chart');
            if (activityCanvas && window.Chart) {
                new Chart(activityCanvas, {
                    type: 'bar',
                    data: { labels: @json(collect($activityChart)->pluck('label')), datasets: [{ label: 'Aktivitas', data: @json(collect($activityChart)->pluck('count')), backgroundColor: '#8dbcf5', hoverBackgroundColor: '#2166d5', borderRadius: 8, maxBarThickness: 42 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
                });
            }
        </script>
    @endpush
@endif
