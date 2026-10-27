<?php

namespace Tests\Feature;

use App\Models\Discussion;
use App\Models\DiscussionCategory;
use App\Models\DiscussionReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscussionForumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function student(): User
    {
        return User::where('role', 'siswa')->firstOrFail();
    }

    private function otherStudent(): User
    {
        return User::where('role', 'siswa')->skip(1)->firstOrFail();
    }

    private function teacher(): User
    {
        return User::where('role', 'guru')->firstOrFail();
    }

    public function test_guest_is_redirected_and_student_can_access_discussion_index(): void
    {
        $this->get('/siswa/zona-santai/ruang-diskusi')->assertRedirect('/');

        $student = $this->student();
        $response = $this->actingAs($student)->get('/siswa/zona-santai/ruang-diskusi');

        $response->assertOk()
            ->assertSee('Ruang Diskusi')
            ->assertSee('Buat Diskusi')
            ->assertSee('Semua Kategori')
            ->assertSee('Total Diskusi')
            ->assertSee('SQL Dasar')
            ->assertSee('SELECT dan WHERE')
            ->assertSee('JOIN dan Relasi');
    }

    public function test_leisure_zone_card_displays_ruang_diskusi_and_links_to_forum(): void
    {
        $student = $this->student();
        $response = $this->actingAs($student)->get('/siswa/zona-santai');

        $response->assertOk()
            ->assertSee('Ruang Diskusi')
            ->assertSee('Bertanya, berbagi solusi SQL, dan berdiskusi bersama teman.')
            ->assertSee('Buka Diskusi')
            ->assertSee(route('siswa.fun-zone.discussions'));
    }

    public function test_student_can_create_a_new_discussion_topic(): void
    {
        $student = $this->student();
        $category = DiscussionCategory::where('slug', 'sql-dasar')->firstOrFail();

        $data = [
            'title' => 'Cara mengurutkan hasil query dengan ORDER BY DESC',
            'discussion_category_id' => $category->id,
            'content' => 'Bagaimana cara mengurutkan nilai siswa dari yang terbesar ke terkecil pada tabel nilai?',
            'sql_snippet' => 'SELECT nama, nilai FROM siswa ORDER BY nilai DESC;',
        ];

        $response = $this->actingAs($student)
            ->post('/siswa/zona-santai/ruang-diskusi', $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('discussions', [
            'user_id' => $student->id,
            'title' => $data['title'],
            'discussion_category_id' => $category->id,
            'status' => 'belum_terjawab',
        ]);
    }

    public function test_discussion_validation_enforces_required_fields(): void
    {
        $student = $this->student();

        $response = $this->actingAs($student)
            ->post('/siswa/zona-santai/ruang-diskusi', [
                'title' => '',
                'discussion_category_id' => 9999,
                'content' => 'pendek',
            ]);

        $response->assertSessionHasErrors(['title', 'discussion_category_id', 'content']);
    }

    public function test_student_can_reply_to_a_discussion_topic(): void
    {
        $student = $this->student();
        $otherStudent = $this->otherStudent();
        $discussion = Discussion::firstOrFail();

        $replyData = [
            'content' => 'Kamu bisa menggunakan klausa DESC setelah nama kolom untuk urutan menurun.',
            'sql_snippet' => 'ORDER BY kolom DESC',
        ];

        $response = $this->actingAs($otherStudent)
            ->post('/siswa/zona-santai/ruang-diskusi/'.$discussion->slug.'/balas', $replyData);

        $response->assertRedirect('/siswa/zona-santai/ruang-diskusi/'.$discussion->slug);

        $this->assertDatabaseHas('discussion_replies', [
            'discussion_id' => $discussion->id,
            'user_id' => $otherStudent->id,
            'content' => $replyData['content'],
            'is_solution' => false,
        ]);

        $this->assertDatabaseHas('discussions', [
            'id' => $discussion->id,
            'replies_count' => $discussion->fresh()->replies_count,
        ]);
    }

    public function test_topic_author_can_mark_and_unmark_a_solution(): void
    {
        $author = $this->student();
        $replier = $this->otherStudent();
        $category = DiscussionCategory::firstOrFail();

        $discussion = Discussion::create([
            'user_id' => $author->id,
            'discussion_category_id' => $category->id,
            'title' => 'Pertanyaan solusi tes',
            'slug' => 'pertanyaan-solusi-tes',
            'content' => 'Isi pertanyaan yang akan diuji solusinya.',
            'status' => 'belum_terjawab',
        ]);

        $reply = DiscussionReply::create([
            'discussion_id' => $discussion->id,
            'user_id' => $replier->id,
            'content' => 'Ini jawaban yang sangat membantu.',
        ]);

        // Non-author student cannot mark solution
        $this->actingAs($replier)
            ->post('/siswa/zona-santai/ruang-diskusi/'.$discussion->slug.'/solusi/'.$reply->id)
            ->assertForbidden();

        // Topic author marks solution
        $this->actingAs($author)
            ->post('/siswa/zona-santai/ruang-diskusi/'.$discussion->slug.'/solusi/'.$reply->id)
            ->assertRedirect();

        $this->assertSame('terjawab', $discussion->fresh()->status);
        $this->assertSame($reply->id, $discussion->fresh()->solved_reply_id);
        $this->assertTrue((bool) $reply->fresh()->is_solution);

        // Topic author unmarks solution (toggle)
        $this->actingAs($author)
            ->post('/siswa/zona-santai/ruang-diskusi/'.$discussion->slug.'/solusi/'.$reply->id)
            ->assertRedirect();

        $this->assertSame('belum_terjawab', $discussion->fresh()->status);
        $this->assertNull($discussion->fresh()->solved_reply_id);
        $this->assertFalse((bool) $reply->fresh()->is_solution);
    }

    public function test_student_cannot_delete_other_student_discussion_or_reply(): void
    {
        $author = $this->student();
        $other = $this->otherStudent();
        $category = DiscussionCategory::firstOrFail();

        $discussion = Discussion::create([
            'user_id' => $author->id,
            'discussion_category_id' => $category->id,
            'title' => 'Diskusi author asli',
            'slug' => 'diskusi-author-asli',
            'content' => 'Isi diskusi author asli.',
        ]);

        $reply = DiscussionReply::create([
            'discussion_id' => $discussion->id,
            'user_id' => $author->id,
            'content' => 'Balasan author asli.',
        ]);

        // Other student attempts deletion
        $this->actingAs($other)
            ->delete('/siswa/zona-santai/ruang-diskusi/'.$discussion->slug)
            ->assertForbidden();

        $this->actingAs($other)
            ->delete('/siswa/zona-santai/ruang-diskusi/'.$discussion->slug.'/balas/'.$reply->id)
            ->assertForbidden();

        // Discussion and reply still exist
        $this->assertDatabaseHas('discussions', ['id' => $discussion->id]);
        $this->assertDatabaseHas('discussion_replies', ['id' => $reply->id]);
    }

    public function test_author_can_delete_own_discussion_and_reply(): void
    {
        $author = $this->student();
        $category = DiscussionCategory::firstOrFail();

        $discussion = Discussion::create([
            'user_id' => $author->id,
            'discussion_category_id' => $category->id,
            'title' => 'Diskusi author sendiri',
            'slug' => 'diskusi-author-sendiri',
            'content' => 'Konten yang akan dihapus author.',
        ]);

        $reply = DiscussionReply::create([
            'discussion_id' => $discussion->id,
            'user_id' => $author->id,
            'content' => 'Balasan yang akan dihapus author.',
        ]);

        $this->actingAs($author)
            ->delete('/siswa/zona-santai/ruang-diskusi/'.$discussion->slug.'/balas/'.$reply->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('discussion_replies', ['id' => $reply->id]);

        $this->actingAs($author)
            ->delete('/siswa/zona-santai/ruang-diskusi/'.$discussion->slug)
            ->assertRedirect();

        $this->assertDatabaseMissing('discussions', ['id' => $discussion->id]);
    }

    public function test_search_and_filters_work_on_discussions(): void
    {
        $student = $this->student();
        $this->actingAs($student);

        // Search
        $response = $this->get('/siswa/zona-santai/ruang-diskusi?q=agregasi');
        $response->assertOk()
            ->assertSee('Mengapa query agregasi COUNT')
            ->assertDontSee('Perbedaan INNER JOIN dan LEFT JOIN');

        // Status filter: terjawab
        $responseSolved = $this->get('/siswa/zona-santai/ruang-diskusi?status=terjawab');
        $responseSolved->assertOk()
            ->assertSee('Perbedaan INNER JOIN dan LEFT JOIN');

        // Status filter: belum_terjawab
        $responseUnsolved = $this->get('/siswa/zona-santai/ruang-diskusi?status=belum_terjawab');
        $responseUnsolved->assertOk()
            ->assertSee('Mengapa query agregasi COUNT');
    }
}
