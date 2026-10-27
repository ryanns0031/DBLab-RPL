<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\User;
use App\Services\YouTubeVideoUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YouTubeMaterialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_supported_youtube_url_formats_are_reduced_to_a_valid_video_id(): void
    {
        foreach ([
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'https://www.youtube.com/shorts/dQw4w9WgXcQ',
        ] as $url) {
            $this->assertSame('dQw4w9WgXcQ', YouTubeVideoUrl::videoId($url));
        }

        $this->assertNull(YouTubeVideoUrl::videoId('https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ'));
        $this->assertNull(YouTubeVideoUrl::videoId('http://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertNull(YouTubeVideoUrl::videoId('javascript:alert(1)'));
    }

    public function test_teacher_can_create_update_and_remove_video_while_student_view_remains_safe(): void
    {
        $teacher = User::where('role', 'guru')->firstOrFail();
        $classId = $teacher->classes()->value('classes.id');
        $student = User::where('role', 'siswa')->where('class_id', $classId)->firstOrFail();
        $data = [
            'title' => 'Video SQL Dasar',
            'description' => 'Materi SELECT',
            'content' => 'Isi materi tetap tersedia.',
            'class_id' => $classId,
            'week' => 1,
            'sort_order' => 1,
            'status' => 'aktif',
        ];

        $this->actingAs($teacher)
            ->post('/guru/materials', $data)
            ->assertSessionHasNoErrors()
            ->assertRedirect('/guru/materials');

        $material = Material::where('title', $data['title'])->firstOrFail();
        $this->assertNull($material->youtube_url);

        $this->post('/guru/materials/'.$material->id, $data + ['youtube_url' => 'https://youtu.be/dQw4w9WgXcQ'])
            ->assertSessionHasNoErrors();
        $this->assertSame('https://youtu.be/dQw4w9WgXcQ', $material->fresh()->youtube_url);

        $this->get('/guru/materials?edit='.$material->id)
            ->assertOk()
            ->assertSee('type="url"', false)
            ->assertSee('value="https://youtu.be/dQw4w9WgXcQ"', false)
            ->assertSee('Link Video YouTube');

        $this->actingAs($student)
            ->get('/siswa/materials/'.$material->id)
            ->assertOk()
            ->assertSee('https://www.youtube.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('loading="lazy"', false)
            ->assertSee('Isi materi tetap tersedia.')
            ->assertSee('buka video di YouTube');

        $this->actingAs($teacher)
            ->post('/guru/materials/'.$material->id, $data + ['youtube_url' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($material->fresh()->youtube_url);

        $this->actingAs($student)
            ->get('/siswa/materials/'.$material->id)
            ->assertOk()
            ->assertDontSee('youtube-video')
            ->assertSee('Isi materi tetap tersedia.');
    }

    public function test_teacher_form_rejects_non_youtube_hosts(): void
    {
        $teacher = User::where('role', 'guru')->firstOrFail();
        $classId = $teacher->classes()->value('classes.id');

        $this->actingAs($teacher)
            ->post('/guru/materials', [
                'title' => 'URL Tidak Valid',
                'description' => 'Test',
                'content' => 'Isi',
                'class_id' => $classId,
                'week' => 1,
                'sort_order' => 1,
                'status' => 'aktif',
                'youtube_url' => 'https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ',
            ])
            ->assertSessionHasErrors('youtube_url');
    }
}
