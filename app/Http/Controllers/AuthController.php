<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login(Request $r)
    {
        $credentials = $r->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', strtolower($credentials['email']))->first();

        if (! $user || ! $user->password || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'Email atau password salah.']);
        }

        if ($user->status !== 'aktif') {
            return back()->withErrors(['email' => 'Akun Anda sedang tidak aktif. Silakan hubungi Administrator.']);
        }

        Auth::login($user);
        $r->session()->regenerate();
        Audit::log('Login email berhasil');

        return redirect('/'.$user->role.'/dashboard');
    }

    public function redirect()
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect('/')->withErrors(['login' => 'Login Google belum dikonfigurasi. Silakan hubungi Administrator.']);
        }

        return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(Request $r)
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect('/')->withErrors(['login' => 'Login Google tidak berhasil atau sesi kedaluwarsa. Silakan coba lagi.']);
        }
        $email = strtolower($google->getEmail() ?? '');
        $domain = strtolower(config('services.google.allowed_domain') ?? '');
        if (! ($google->user['verified_email'] ?? $google->user['email_verified'] ?? false) || ($domain && substr(strrchr($email, '@') ?: '', 1) !== $domain)) {
            return redirect('/')->withErrors(['login' => 'Email Google tidak terverifikasi atau domain tidak diizinkan.']);
        }
        $u = User::where('email', $email)->first();
        if (! $u) {
            return redirect('/')->withErrors(['login' => 'Akun Google Anda belum terdaftar di DBLab RPL. Silakan hubungi Administrator.']);
        }
        if ($u->status !== 'aktif') {
            return redirect('/')->withErrors(['login' => 'Akun Anda sedang tidak aktif. Silakan hubungi Administrator.']);
        }
        if ($u->google_id && $u->google_id !== (string) $google->getId()) {
            return redirect('/')->withErrors(['login' => 'Identitas Google tidak cocok. Silakan hubungi Administrator.']);
        }
        $u->update(['google_id' => (string) $google->getId(), 'avatar' => $google->getAvatar(), 'email_verified_at' => now()]);
        Auth::login($u);
        $r->session()->regenerate();
        Audit::log('Login Google berhasil');

        return redirect('/'.$u->role.'/dashboard');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/');
    }
}
