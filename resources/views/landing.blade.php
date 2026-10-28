@extends('layout')
@section('title','Masuk')
@section('content')
<div class="landing-grid">
  <section class="landing-story">
    <div class="eyebrow"><span class="live-dot"></span> LABORATORIUM BELAJAR DIGITAL · SMK RPL</div>
    <h1>Data punya cerita.<br><span>Kamu yang<br>menemukannya.</span></h1>
    <p class="lead">Kuasai Basis Data dari query pertamamu hingga project nyata. Satu ruang untuk belajar, bereksperimen, dan bertumbuh.</p>
    <div class="learning-pills"><span>01 &nbsp; Learn</span><span>02 &nbsp; Practice</span><span>03 &nbsp; Build</span></div>
    <div class="sql-scene">
      <div class="editor-bar"><span>● ● ●</span><small>eksplorasi.sql</small><span>SQLite</span></div>
      <div class="code-lines">
        <span class="line-no">1</span> <b>SELECT</b> nama, kelas<br>
        <span class="line-no">2</span> <b>FROM</b> siswa<br>
        <span class="line-no">3</span> <b>WHERE</b> semangat = <em>'tinggi'</em>;<span class="cursor">▌</span>
      </div>
      <div class="scene-footer"><span class="text-success">✓ Siap untuk dieksplorasi</span><span>∞ kemungkinan</span></div>
    </div>
    <div class="float-chip">▤ &nbsp; db_masa_depan</div>
  </section>
  <section class="login-card">
    <span class="login-emblem">↗</span>
    <div class="eyebrow">SELAMAT DATANG</div>
    <h2>Masuk ke<br>Akunmu.</h2>
    <p>Masukkan email dan password untuk melanjutkan pembelajaran.</p>
    
    @if ($errors->any())
      <div class="alert alert-danger">
        @foreach ($errors->all() as $error)
          <div>{{ $error }}</div>
        @endforeach
      </div>
    @endif

    <form method="POST" action="/login" class="login-form">
      @csrf
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Masukkan password" required>
      </div>
      <button type="submit" class="btn-login">Masuk <span>→</span></button>
    </form>
    
    <div class="login-hint">Gunakan akun yang sudah didaftarkan oleh Administrator sekolah.</div>
    <hr>
    <small>Belum dapat masuk?<br><b>Hubungi Administrator sekolah.</b></small>
  </section>
</div>
<div class="landing-bottom"><span>DIRANCANG UNTUK SISWA RPL</span><div>Materi terarah</div><div>SQL Lab interaktif</div><div>Challenge & project</div><div>Progress terukur</div></div>
<style>
.login-form { margin: 1.5rem 0; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.3rem; color: #555; }
.form-group input { width: 100%; padding: 0.7rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem; outline: none; transition: border-color 0.2s; }
.form-group input:focus { border-color: #4f46e5; }
.btn-login { width: 100%; padding: 0.7rem; background: #4f46e5; color: #fff; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
.btn-login:hover { background: #4338ca; }
.alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.85rem; }
</style>
@endsection
