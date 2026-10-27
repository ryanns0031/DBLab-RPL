<?php

namespace App\Http\Middleware;

use Closure;

class RoleMiddleware
{
    public function handle($request, Closure $next, string $role)
    {
        abort_unless($request->user() && $request->user()->status === 'aktif', 403, 'Akun tidak aktif. Hubungi Administrator.');
        abort_unless($request->user()->role === $role, 403, 'Anda tidak memiliki akses ke halaman ini.');

        return $next($request);
    }
}
