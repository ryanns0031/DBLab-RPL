<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','Belajar Basis Data') · DBLab RPL</title>
    <link rel="stylesheet" href="/assets/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}?v=20261028-showcase">
    <script>try{document.documentElement.dataset.bsTheme=localStorage.getItem('dblab-theme')||'light'}catch(e){}</script>
</head>
<body>
@php($isStudentWorkspace = auth()->check() && auth()->user()->role === 'siswa' && request()->is('siswa/*'))
@php($isStudentDashboard = $isStudentWorkspace && request()->is('siswa/dashboard'))
@php($isStaffWorkspace = auth()->check() && in_array(auth()->user()->role, ['guru', 'admin'], true) && (request()->is('guru/*') || request()->is('admin/*') || request()->is('review/pameran-karya*')))
@php($isStaffDashboard = $isStaffWorkspace && request()->is(auth()->user()->role.'/dashboard'))
@if($isStudentWorkspace)
    @php($studentNav = [
        'dashboard' => ['label' => 'Dashboard', 'icon' => '⌂'],
        'pretest' => ['label' => 'Pre-test', 'icon' => '▣'],
        'materials' => ['label' => 'Materi', 'icon' => '▤'],
        'lab' => ['label' => 'SQL Lab', 'icon' => '⌘'],
        'challenges' => ['label' => 'Challenge', 'icon' => '♜'],
        'projects' => ['label' => 'Project', 'icon' => '◇'],
        'posttest' => ['label' => 'Post-test', 'icon' => '▣'],
        'progress' => ['label' => 'Progress', 'icon' => '◷'],
        'zona-santai' => ['label' => 'Zona Santai', 'icon' => '♧'],
    ])
    @php($studentTestRoute = request()->route('test'))
    @php($studentNavActive = trim($__env->yieldContent('student-nav-active')) ?: ($studentTestRoute instanceof \App\Models\LearningTest ? $studentTestRoute->type : ''))
    <header class="student-dashboard-nav">
        <a class="student-nav-brand" href="/siswa/dashboard" aria-label="DBLab RPL, Dashboard">
            <span class="student-brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32"><ellipse cx="16" cy="7" rx="10" ry="4"/><path d="M6 7v6c0 2.2 4.5 4 10 4s10-1.8 10-4V7M6 13v6c0 2.2 4.5 4 10 4s10-1.8 10-4v-6M6 19v6c0 2.2 4.5 4 10 4s10-1.8 10-4v-6"/></svg></span>
            <span>DBLab <b>RPL</b></span>
        </a>
        <nav class="student-nav-links" aria-label="Navigasi siswa">
            @foreach($studentNav as $path => $item)
                <a href="/siswa/{{ $path }}" @if($studentNavActive ? $studentNavActive === $path : request()->is('siswa/'.$path.'*')) aria-current="page" @endif><span aria-hidden="true">{{ $item['icon'] }}</span>{{ $item['label'] }}</a>
            @endforeach
        </nav>
        <div class="student-nav-actions">
            <details class="student-mobile-nav">
                <summary aria-label="Buka navigasi">☰ <span>Menu</span></summary>
                <nav aria-label="Navigasi siswa">
                    @foreach($studentNav as $path => $item)
                        <a href="/siswa/{{ $path }}" @if($studentNavActive ? $studentNavActive === $path : request()->is('siswa/'.$path.'*')) aria-current="page" @endif><span aria-hidden="true">{{ $item['icon'] }}</span>{{ $item['label'] }}</a>
                    @endforeach
                </nav>
            </details>
            <button id="theme-toggle" class="student-theme-toggle" type="button" aria-label="Ganti mode warna">Mode</button>
            <details class="student-profile-menu">
                <summary><span class="student-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span class="student-profile-name">{{ auth()->user()->name }}<small>Siswa</small></span><span class="student-profile-chevron" aria-hidden="true">⌄</span></summary>
                <div class="student-profile-dropdown"><b>{{ auth()->user()->name }}</b><small>Akun siswa</small><form action="/logout" method="post">@csrf<button type="submit">Keluar</button></form></div>
            </details>
        </div>
    </header>
@elseif($isStaffWorkspace)
    @php($staffRole = auth()->user()->role)
    @php($staffNav = $staffRole === 'guru' ? [
        ['label' => 'Dashboard', 'icon' => '⌂', 'href' => '/guru/dashboard', 'active' => request()->is('guru/dashboard')],
        ['label' => 'Siswa', 'icon' => '♙', 'href' => '/guru/students', 'active' => request()->is('guru/students*')],
        ['label' => 'Materi', 'icon' => '▤', 'href' => '/guru/materials', 'active' => request()->is('guru/materials*')],
        ['label' => 'Evaluasi', 'icon' => '☑', 'href' => '/guru/tests', 'active' => request()->is('guru/tests*')],
        ['label' => 'Challenge', 'icon' => 'ϟ', 'href' => '/guru/challenges', 'active' => request()->is('guru/challenges*')],
        ['label' => 'Project', 'icon' => '◇', 'href' => '/guru/projects', 'active' => request()->is('guru/projects*')],
        ['label' => 'Analitik', 'icon' => '◷', 'href' => '/guru/analytics', 'active' => request()->is('guru/analytics*')],
        ['label' => 'Pameran', 'icon' => '▧', 'href' => '/review/pameran-karya', 'active' => request()->is('review/pameran-karya*')],
    ] : [
        ['label' => 'Dashboard', 'icon' => '⌂', 'href' => '/admin/dashboard', 'active' => request()->is('admin/dashboard')],
        ['label' => 'Pengguna', 'icon' => '♙', 'href' => '/admin/users', 'active' => request()->is('admin/users') && !request()->query('role')],
        ['label' => 'Siswa', 'icon' => '♧', 'href' => '/admin/users?role=siswa', 'active' => request()->is('admin/users') && request()->query('role') === 'siswa'],
        ['label' => 'Guru', 'icon' => '♙', 'href' => '/admin/users?role=guru', 'active' => request()->is('admin/users') && request()->query('role') === 'guru'],
        ['label' => 'Kelas', 'icon' => '▦', 'href' => '/admin/classes', 'active' => request()->is('admin/classes*')],
        ['label' => 'Pengampu', 'icon' => '♧', 'href' => '/admin/assignments', 'active' => request()->is('admin/assignments*')],
        ['label' => 'Tahun Ajaran', 'icon' => '▣', 'href' => '/admin/years', 'active' => request()->is('admin/years*')],
        ['label' => 'Import', 'icon' => '⇧', 'href' => '/admin/import', 'active' => request()->is('admin/import*')],
        ['label' => 'Aktivitas', 'icon' => '◷', 'href' => '/admin/logs', 'active' => request()->is('admin/logs*')],
        ['label' => 'Pameran', 'icon' => '▧', 'href' => '/review/pameran-karya', 'active' => request()->is('review/pameran-karya*')],
    ])
    <header class="student-dashboard-nav staff-dashboard-nav">
        <a class="student-nav-brand" href="/{{ $staffRole }}/dashboard" aria-label="DBLab RPL, Dashboard">
            <span class="student-brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32"><ellipse cx="16" cy="7" rx="10" ry="4"/><path d="M6 7v6c0 2.2 4.5 4 10 4s10-1.8 10-4V7M6 13v6c0 2.2 4.5 4 10 4s10-1.8 10-4v-6M6 19v6c0 2.2 4.5 4 10 4s10-1.8 10-4v-6"/></svg></span>
            <span>DBLab <b>RPL</b></span>
        </a>
        <nav class="student-nav-links" aria-label="Navigasi {{ $staffRole }}">
            @foreach($staffNav as $item)
                <a href="{{ $item['href'] }}" @if($item['active']) aria-current="page" @endif><span aria-hidden="true">{{ $item['icon'] }}</span>{{ $item['label'] }}</a>
            @endforeach
        </nav>
        <div class="student-nav-actions">
            <details class="student-mobile-nav">
                <summary aria-label="Buka navigasi">☰ <span>Menu</span></summary>
                <nav aria-label="Navigasi {{ $staffRole }}">
                    @foreach($staffNav as $item)
                        <a href="{{ $item['href'] }}" @if($item['active']) aria-current="page" @endif><span aria-hidden="true">{{ $item['icon'] }}</span>{{ $item['label'] }}</a>
                    @endforeach
                </nav>
            </details>
            <button id="theme-toggle" class="student-theme-toggle" type="button" aria-label="Ganti mode warna">Mode</button>
            <details class="student-profile-menu">
                <summary><span class="student-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span class="student-profile-name">{{ auth()->user()->name }}<small>{{ ucfirst($staffRole) }}</small></span><span class="student-profile-chevron" aria-hidden="true">⌄</span></summary>
                <div class="student-profile-dropdown"><b>{{ auth()->user()->name }}</b><small>Akun {{ $staffRole }}</small><form action="/logout" method="post">@csrf<button type="submit">Keluar</button></form></div>
            </details>
        </div>
    </header>
@else
    <header class="topbar"><a class="brand" href="{{ auth()->check()?'/'.auth()->user()->role.'/dashboard':'/' }}"><span class="brand-icon">▤</span> DBLab <span class="muted">RPL</span></a><div class="top-actions"><button id="theme-toggle" class="btn btn-sm btn-outline-secondary" aria-label="Ganti mode warna">Mode Gelap</button>@auth<span class="profile"><span class="avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><span>{{ auth()->user()->name }}<small>{{ ucfirst(auth()->user()->role) }}</small></span></span><form action="/logout" method="post">@csrf<button class="btn btn-sm btn-outline-secondary">Keluar</button></form>@endauth</div></header>
    @auth
        @php($nav=match(auth()->user()->role){'admin'=>['dashboard'=>'Dashboard','users'=>'Kelola Pengguna','classes'=>'Kelola Kelas','assignments'=>'Guru Pengampu','years'=>'Tahun Ajaran','logs'=>'Aktivitas Sistem'],'guru'=>['dashboard'=>'Dashboard','students'=>'Siswa','materials'=>'Materi','tests'=>'Pre/Post Test','challenges'=>'Challenge','projects'=>'Project','analytics'=>'Analytics'],default=>['dashboard'=>'Dashboard','pretest'=>'Pre-test','materials'=>'Materi','lab'=>'SQL Lab','challenges'=>'Challenge','projects'=>'Project','posttest'=>'Post-test','progress'=>'Progress','zona-santai'=>'Zona Santai']})
        <button class="mobile-menu btn btn-primary" aria-controls="sidebar" aria-expanded="false">☰ Menu</button>
        <aside id="sidebar"><span class="eyebrow">RUANG {{ strtoupper(auth()->user()->role) }}</span><nav>@foreach($nav as $path=>$label)<a class="{{ request()->is(auth()->user()->role.'/'.$path.'*')?'selected':'' }}" href="/{{ auth()->user()->role }}/{{ $path }}"><span class="nav-mark">{{ ['dashboard'=>'⌂','lab'=>'⌘','materials'=>'▤','challenges'=>'ϟ','projects'=>'◇','progress'=>'◴','analytics'=>'◧'][$path]??'◌' }}</span>{{ $label }}</a>@endforeach</nav><div class="sidebar-note"><b>Belajar. Praktik. Bangun.</b><p>Satu langkah lebih dekat menjadi database developer.</p><code>SELECT masa_depan<br>FROM potensi_kamu;</code></div><form class="sidebar-logout" action="/logout" method="post">@csrf<button class="btn btn-outline-secondary w-100">Keluar dari DBLab</button></form><small class="sidebar-footer">DBLab RPL · Learning workspace</small></aside>
    @endauth
@endif

<main class="{{ $isStudentWorkspace ? 'student-workspace-main'.($isStudentDashboard ? ' student-dashboard-main' : '') : ($isStaffWorkspace ? 'staff-workspace-main'.($isStaffDashboard ? ' staff-dashboard-main' : '') : (auth()->check()?'workspace':'public-page')) }}">
    @if(session('success'))<div role="status" class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>@endif
    @if(session('feedback'))<div role="status" class="alert alert-info">{{ session('feedback') }}</div>@endif
    @if($errors->any())<div role="alert" class="alert alert-danger"><b>Periksa kembali</b><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
<script src="/assets/bootstrap.bundle.min.js"></script>
<script src="/assets/app.js"></script>
@stack('scripts')
</body>
</html>
