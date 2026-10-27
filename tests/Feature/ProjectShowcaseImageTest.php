<?php

namespace Tests\Feature;

use App\Models\ProjectShowcase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectShowcaseImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_inventory_showcase_uses_the_new_dashboard_image(): void
    {
        $path = 'showcase/thumbnails/inventaris-laboratorium-rpl-dashboard.png';
        $libraryPath = 'showcase/thumbnails/perpustakaan-digital-dashboard.png';
        $showcase = ProjectShowcase::where('slug', 'sistem-inventaris-laboratorium-rpl')->firstOrFail();
        $library = ProjectShowcase::where('slug', 'sistem-perpustakaan-digital')->firstOrFail();

        $this->assertSame($path, $showcase->thumbnail_path);
        $this->assertTrue(Storage::disk('public')->exists($path));
        $this->assertSame($libraryPath, $library->thumbnail_path);
        $this->assertTrue(Storage::disk('public')->exists($libraryPath));

        $this->actingAs(User::where('role', 'siswa')->firstOrFail())
            ->get(route('siswa.fun-zone.showcase'))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($path), false)
            ->assertSee(Storage::disk('public')->url($libraryPath), false)
            ->assertSee('Sistem Inventaris Laboratorium RPL');
    }
}
