<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('dblab:admin {email} {--name=Administrator DBLab}', function () {
    $email = strtolower($this->argument('email'));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Email tidak valid.');

        return 1;
    }
    $existing = User::where('email', $email)->first();
    if ($existing && $existing->role !== 'admin') {
        $this->error('Email sudah digunakan oleh akun non-admin.');

        return 1;
    }
    User::firstOrCreate(['email' => $email], ['name' => $this->option('name'), 'role' => 'admin', 'status' => 'aktif']);
    $this->info('Admin terdaftar. Gunakan Google OAuth untuk masuk: '.$email);
})->purpose('Daftarkan admin Google tanpa membuat password');
