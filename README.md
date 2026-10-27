# DBLab RPL

Platform pembelajaran Basis Data SMK RPL. Laravel 12, MySQL, Blade, Bootstrap 5.3.8, JavaScript vanilla, Chart.js 4.5.1, Laravel Socialite, dan SQLite sandbox.

## Status paket

Kode aplikasi, migration, model, factory, seeder, halaman Blade, aset frontend lokal, buku panduan PDF, dan pengujian tersedia. Dependencies PHP disertakan dalam `vendor/` bersama `composer.lock`. Tidak ada password Gmail, kredensial Google, `.env`, atau database berisi sesi pengguna dalam paket.

**Aplikasi belum dipublikasikan.** Login Google memerlukan kredensial OAuth milik sekolah. Pengujian otomatis menggunakan SQLite dan provider Google simulasi; verifikasi MySQL dan login Google nyata tetap harus dilakukan pada lingkungan tujuan. Browser visual QA tidak tersedia di lingkungan pembuatan. Rincian verifikasi ada di `TEST-REPORT.md`.

## Persyaratan

- PHP 8.3 atau 8.4 dengan ekstensi `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `iconv`, `mbstring`, `openssl`, `pdo_mysql`, `pdo_sqlite`, `sqlite3`, `xml`, `xmlreader`, `xmlwriter`, `zip`.
- Composer 2, MySQL 8.x. Dependencies yang dikunci telah diuji pada PHP 8.4.
- Fungsi `proc_open` harus tersedia untuk menjalankan SQL sandbox terisolasi. Proses PHP CLI menggunakan binary yang sama dengan aplikasi; jika memakai PHP-FPM, atur `SQL_SANDBOX_PHP_BINARY` ke path PHP CLI.
- Direktori `storage/` dan `bootstrap/cache/` harus dapat ditulis web server.
- Tidak membutuhkan Node/npm untuk menjalankan frontend; aset Bootstrap dan Chart.js tersedia lokal.

## Menjalankan di Windows / lokal

1. Ekstrak ZIP lalu buka terminal di folder `DBLab-RPL`.
2. Periksa `php -v` dan `composer --version`. Aktifkan ekstensi di atas melalui `php.ini`.
3. Jalankan `composer install`. Langkah ini juga memverifikasi persyaratan PHP pada komputer Anda. Folder vendor sudah disertakan untuk membantu setup.
4. Salin `.env.example` menjadi `.env` (`copy .env.example .env` di Command Prompt).
5. Buat database MySQL, misalnya melalui klien MySQL atau phpMyAdmin:

```sql
CREATE DATABASE dblab_rpl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

6. Sesuaikan koneksi pada `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dblab_rpl
DB_USERNAME=nama_user_mysql_anda
DB_PASSWORD=password_mysql_anda
ADMIN_EMAIL=email_google_admin_sekolah_anda
```

Gunakan user MySQL khusus yang memiliki izin pada database `dblab_rpl`. Untuk Docker MySQL, `compose.yaml` tersedia: isi `DB_PASSWORD` dan `MYSQL_ROOT_PASSWORD` pada `.env`, lalu `docker compose up -d mysql`.

7. Isi Google OAuth seperti bagian berikut, kemudian:

```bash
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

8. Buka **http://localhost:8000**. Gunakan host yang sama dengan `APP_URL` dan redirect OAuth.

Seeder idempoten: tidak menimpa akun yang sudah ada. Pada produksi, gunakan seeder admin tersendiri (di bawah), jangan isi data demo.

### Alternatif pengujian tanpa MySQL

SQLite dapat dipakai untuk menjalankan aplikasi secara lokal sementara. Buat file kosong `database/database.sqlite`, lalu atur `DB_CONNECTION=sqlite` dan `DB_DATABASE` ke path absolut file tersebut. Jalankan migrasi/seeder. SQL Lab selalu menggunakan SQLite in-memory terpisah, termasuk ketika database utama menggunakan MySQL.

## Google OAuth

1. Buka https://console.cloud.google.com/ dan pilih atau buat proyek sekolah.
2. Pada Google Auth Platform, isi informasi Branding dan Audience (Internal untuk organisasi Workspace yang sesuai; External bila menggunakan akun Gmail biasa).
3. Gunakan scope minimal profil, email, dan OpenID. Ketika aplikasi berstatus Testing, tambahkan email pengguna ke Test users.
4. Buat OAuth Client jenis **Web application** pada bagian Clients.
5. Tambahkan authorized redirect URI yang sama persis:
   `http://localhost:8000/auth/google/callback`
6. Isi `.env` di komputer/server Anda, jangan kirim Client Secret melalui chat:

```dotenv
APP_URL=http://localhost:8000
GOOGLE_CLIENT_ID=client_id_dari_google
GOOGLE_CLIENT_SECRET=client_secret_dari_google
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
GOOGLE_ALLOWED_DOMAIN=
```

7. Jalankan `php artisan config:clear`. Pastikan email admin Anda cocok dengan `ADMIN_EMAIL` yang digunakan saat seeding. Bila sudah terlanjur seeding, ubah email melalui CLI aman:

```bash
php artisan dblab:admin admin@sekolah.sch.id
```

Perintah hanya mendaftarkan admin pada database; tidak membuat akun Google atau password.

8. Klik Masuk dengan Google. Email harus sudah terdaftar dan aktif. Set `GOOGLE_ALLOWED_DOMAIN=sekolah.sch.id` untuk membatasi domain; kosong berarti semua domain Google boleh masuk **hanya jika email sudah terdaftar**.
9. Untuk produksi, gunakan HTTPS, redirect URI HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, dan `APP_URL` sesuai domain. Atur document root ke `public/`, bukan root proyek. Periksa host/proxy tepercaya sesuai server.

Implementasi Socialite memvalidasi state; tidak menggunakan `stateless()`. Email harus terverifikasi, domain cocok bila dibatasi, akun terdaftar aktif, dan Google subject konsisten. Logout membatalkan sesi DBLab saja.

Referensi: https://laravel.com/docs/12.x/socialite dan https://developers.google.com/identity/protocols/oauth2/web-server

## Email contoh

| Peran | Email | Keterangan |
| --- | --- | --- |
| Admin | `admin.dblab@gmail.com` | Diganti melalui `ADMIN_EMAIL` sebelum seed |
| Guru | `guru.dblab@gmail.com` | Ibu Sari, XI RPL 1 dan XI RPL 2 |
| Siswa | `siswa.dblab@gmail.com` | Rizky, NIS 20260123, XI RPL 1 |

Email tersebut **hanya record demo**. Paket tidak menyediakan akses ke akun Google itu. Gunakan email Google yang Anda miliki untuk login nyata; admin kemudian mendaftarkan guru dan siswa. Andi/Rina/Budi menggunakan email `example.com` sebagai data contoh.

## Alur penggunaan

- **Admin:** kelola tahun ajaran, kelas, pengguna, import siswa melalui preview CSV/XLSX, guru pengampu, dan aktivitas sistem.
- **Guru:** materi teks/PDF/DOCX, soal pre/post-test, challenge, project/tahapan/deadline, penilaian project, filter siswa, serta analytics kelas yang diampu.
- **Siswa:** membaca/mengunduh materi, mengerjakan tes sekali, menjalankan SQL, mengirim challenge, memperbarui checklist dan pengumpulan project, melihat nilai dan progress.
- Buku panduan PDF di header dapat diunduh siapa pun. Dark/light mode disimpan pada browser.

Tes disalin saat mulai untuk menjaga konsistensi penilaian. Tidak ada kunci jawaban dalam HTML tes siswa. Challenge menyimpan satu record progres dengan penghitung percobaan; XP hanya sekali. Checklist project adalah kemajuan mandiri, bukan nilai guru. Mengirim ulang project sebelum deadline mengosongkan nilai agar dapat ditinjau kembali.

## SQL sandbox

`SqlSandbox` menjalankan `scripts/sql-sandbox.php` melalui proses PHP CLI terpisah dengan batas waktu 3 detik dan memori 48 MB. Proses tidak memuat Laravel atau konfigurasi database utama. Setiap query memperoleh database SQLite in-memory baru: `siswa`, `pelanggan`, `pesanan`.

Hanya satu SELECT, maksimal 4.000 karakter dan 200 baris hasil. Authorizer SQLite membatasi tabel dan fungsi. ATTACH, PRAGMA, tabel internal, multiple statements, extension loading, dan fungsi alokasi besar ditolak. JOIN, agregat, WHERE, dan subquery SELECT didukung. SQL menggunakan dialek SQLite; DDL/DML untuk project dikerjakan terpisah di MySQL lokal.

Hasil challenge dibandingkan sebagai multiset nilai baris, urutan baris dan nama alias kolom diabaikan; urutan nilai kolom harus sama. Angka dinormalisasi ke string. NULL dan baris duplikat tetap dibedakan. Seluruh query jawaban guru harus dapat dijalankan pada sandbox yang sama.

## Perhitungan dan batas versi

- Progress total: kegiatan selesai / kegiatan aktif; setiap materi, challenge, tes, dan langkah project dihitung satu unit. SQL Lab tidak menambah progress langsung.
- Progress topik: materi ditandai selesai + challenge terkait yang berhasil.
- Analytics memakai data database, bukan angka demo. Peningkatan dihitung dari pasangan siswa yang sudah memiliki nilai pre/post-test. Nilai rata-rata awal/akhir ditampilkan terpisah dengan kemungkinan jumlah peserta berbeda.
- Persentase materi selesai tidak otomatis membuktikan kesulitan materi. Analytics memberi indikator peninjauan.
- Model menggunakan satu kelas utama per siswa. Pivot `class_students` disinkronkan untuk keanggotaan; riwayat perpindahan multi-tahun belum menjadi fitur.
- Badge WHERE Hunter muncul setelah siswa menyelesaikan challenge terkait materi minggu 6. Badge dihitung dari pencapaian tersimpan.
- Import maksimal 500 baris/2 MB, ukuran XLSX setelah dekompresi maksimal 20 MB. Materi/project maksimal 10 MB. Lampiran disimpan privat di Laravel Storage dan diunduh melalui pemeriksaan akses.
- Nonaktifkan konten untuk mempertahankan histori. Menghapus tes/project/challenge dapat menghapus progress terkait, dengan konfirmasi UI.

## Struktur proyek

```text
app/Http/Controllers/      Auth, Admin, Import, Teacher, Student, Dashboard
app/Http/Middleware/       RoleMiddleware: peran dan status aktif
app/Models/                17 model domain dan relasi
app/Policies/              LearningPolicy: batas akses kelas
app/Services/              SqlSandbox, Progress, Audit
bootstrap/app.php          Routing dan middleware Laravel
config/                    Konfigurasi Laravel dan Google
routes/web.php             Route web + CSRF/session Laravel
routes/console.php         Perintah provisioning admin
scripts/sql-sandbox.php    Proses query SQLite terisolasi
resources/views/           Blade landing, dashboard, dan seluruh modul
resources/guide/           Buku panduan PDF
public/assets/             Bootstrap, Chart.js, CSS, JS, template import
lang/id/validation.php     Pesan validasi Bahasa Indonesia
database/migrations/       19 tabel domain, foreign key, constraint
database/seeders/          Data awal, materi 12 minggu, tes dan project
database/factories/        Factory pengguna dan konten pembelajaran
tests/Feature/             Pengujian akses, login, import, dan pembelajaran
```

## Pengujian dan pemeliharaan

```bash
php artisan test
php artisan view:cache
php artisan route:list
```

`phpunit.xml` memakai SQLite in-memory sehingga tidak menghapus data utama. Untuk menguji MySQL, gunakan database **khusus testing**, salin konfigurasi PHPUnit dan ubah DB_CONNECTION/DB_DATABASE/DB_USERNAME/DB_PASSWORD khusus testing. Jangan arahkan `RefreshDatabase` ke database sekolah.

Backup database MySQL serta `storage/app/private`. Jangan mempublikasikan `.env`, `storage/logs`, atau direktori vendor. Jangan gunakan `migrate:fresh` pada data sekolah. Tambahkan email asli lewat admin dan hapus data demo sebelum penggunaan nyata. Untuk deployment, jalankan `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan config:cache`, `php artisan view:cache`.
