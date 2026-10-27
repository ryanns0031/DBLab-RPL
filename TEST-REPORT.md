# Laporan verifikasi DBLab RPL

## Hasil

- PHPUnit melalui `php artisan test --compact`: **26 passed, 175 assertions**.
- Runtime: PHP 8.4.14; database test SQLite in-memory.
- Migrasi 19 tabel domain berhasil. Seeder menghasilkan pengguna contoh, 2 kelas, 12 materi per kelas, pre/post-test, challenge, dan project 8 tahap.
- `php artisan migrate:status`: migration utama berstatus Ran.
- `php artisan view:cache`: berhasil.
- Pemeriksaan `php -l` pada 56 file PHP aplikasi, migration, factory, seeder, konfigurasi, routes, dan sandbox: tidak ada kesalahan sintaks.
- `node --check public/assets/app.js`: berhasil.
- Buku panduan PDF 4 halaman dirender dan diperiksa: teks terbaca, tidak terpotong.

## Cakupan pengujian

1. Rendering landing, dashboard setiap peran, halaman pengelolaan, halaman detail, dan buku panduan.
2. Penolakan akses dashboard lintas peran.
3. Penolakan akses siswa/guru ke kelas yang tidak berhak diakses.
4. Penolakan pengguna nonaktif.
5. CRUD admin untuk pengguna, kelas, tahun ajaran, dan guru pengampu.
6. Perlindungan admin dari penghapusan atau penonaktifan dirinya sendiri.
7. Upload/download materi dan penandaan selesai yang idempoten.
8. CRUD tes, soal, challenge, project, serta tahap project oleh guru.
9. Penilaian tes memakai snapshot, validasi jawaban lengkap, dan penolakan submit kedua.
10. Pemberian XP challenge hanya sekali.
11. Checklist project, pengumpulan, penilaian guru, deadline, dan isolasi data.
12. Preview serta commit import CSV/XLSX termasuk baris invalid dan duplikat.
13. Sandbox SELECT, WHERE, JOIN, agregat, hasil query, dan umpan balik kesalahan.
14. Penolakan DELETE, ATTACH, tabel internal, extension loading, multiple statements, dan fungsi alokasi besar.
15. OAuth: hanya akun terdaftar, verifikasi email, domain, status aktif, kecocokan Google subject, redirect peran, dan validasi state.
16. Buku panduan PDF dapat diunduh tanpa login.

## Batas verifikasi

- Login Google diuji menggunakan provider simulasi; callback state salah diuji menggunakan provider Socialite nyata sebelum akses jaringan. Login akun Google nyata belum diuji karena Client ID/Secret sekolah belum tersedia.
- Database utama dikonfigurasi untuk MySQL dalam `.env.example`. Migrasi dan fitur belum dijalankan terhadap MySQL pada lingkungan ini karena servernya tidak tersedia dan pemasangan paket sistem dibatasi izin. SQLite digunakan untuk pengujian backend.
- Browser otomatis tidak tersedia untuk QA visual/responsif. Pengujian rendering HTTP/Blade berhasil, tetapi tampilan desktop/mobile dan interaksi JavaScript perlu diperiksa di browser tujuan.
- Deployment/HTTPS belum dilakukan. Tidak ada URL website publik dalam paket ini.
- Pengujian ini bukan audit keamanan produksi atau uji beban. Batas dan cara deployment ada dalam README.
