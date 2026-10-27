<?php

namespace App\Policies;

use App\Models\User;

class LearningPolicy
{
    public function manage(User $u, $item): bool
    {
        return $u->status === 'aktif' && $u->role === 'guru' && $u->classes()->where('classes.id', $item->class_id)->exists();
    }

    public function view(User $u, $item): bool
    {
        return $this->manage($u, $item) || ($u->status === 'aktif' && $u->role === 'siswa' && $u->class_id === $item->class_id && $item->status === 'aktif' && $u->schoolClass?->status === 'aktif');
    }
}
