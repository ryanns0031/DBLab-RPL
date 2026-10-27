<?php

namespace App\Providers;

use App\Models\Challenge;
use App\Models\LearningTest;
use App\Models\Material;
use App\Models\Project;
use App\Policies\LearningPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        foreach ([Material::class, LearningTest::class, Challenge::class, Project::class] as $m) {
            Gate::policy($m, LearningPolicy::class);
        }
        Gate::define('admin', fn ($u) => $u->role === 'admin' && $u->status === 'aktif');
    }
}
