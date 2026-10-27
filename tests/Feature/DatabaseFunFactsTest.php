<?php

namespace Tests\Feature;

use App\Models\DatabaseFunFact;
use App\Models\DatabaseFunFactRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseFunFactsTest extends TestCase
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

    private function teacher(): User
    {
        return User::where('role', 'guru')->firstOrFail();
    }

    public function test_fun_facts_page_is_accessible_by_student_and_protected_from_guest(): void
    {
        $this->get('/siswa/zona-santai/database-fun-facts')->assertRedirect('/');

        $student = $this->student();
        $response = $this->actingAs($student)->get('/siswa/zona-santai/database-fun-facts');

        $response->assertOk()
            ->assertSee('Database Fun Facts')
            ->assertSee('Fakta Menarik')
            ->assertSee('Progress Eksplorasi')
            ->assertSee('Rekomendasi Topik')
            ->assertSee('Tahukah Kamu?');

        // Check breadcrumb
        $response->assertSee('Dashboard')
            ->assertSee('Zona Santai')
            ->assertSee('Database Fun Facts');
    }

    public function test_fun_facts_route_names_work(): void
    {
        $student = $this->student();
        $this->actingAs($student);

        $url = route('siswa.fun-zone.database-fun-facts');
        $this->assertSame(url('/siswa/zona-santai/database-fun-facts'), $url);

        $fact = DatabaseFunFact::active()->firstOrFail();
        $detailUrl = route('siswa.fun-zone.database-fun-facts.show', ['slug' => $fact->slug]);
        $this->assertSame(url('/siswa/zona-santai/database-fun-facts/'.$fact->slug), $detailUrl);
    }

    public function test_category_filters_display_matching_facts(): void
    {
        $student = $this->student();
        $this->actingAs($student);

        // Filter: Dunia Nyata
        $response = $this->get('/siswa/zona-santai/database-fun-facts?category=Dunia+Nyata');
        $response->assertOk()
            ->assertSee('Database di Balik Media Sosial')
            ->assertSee('Database di Transportasi Online');

        // Filter: Game & Hiburan
        $responseGame = $this->get('/siswa/zona-santai/database-fun-facts?category=Game+%26+Hiburan');
        $responseGame->assertOk()
            ->assertSee('Database dalam Dunia Game')
            ->assertDontSee('Database di Lingkungan Sekolah');

        // Filter: Edukasi
        $responseEdu = $this->get('/siswa/zona-santai/database-fun-facts?category=Edukasi');
        $responseEdu->assertOk()
            ->assertSee('Database di Lingkungan Sekolah');

        // Filter: Bisnis
        $responseBiz = $this->get('/siswa/zona-santai/database-fun-facts?category=Bisnis');
        $responseBiz->assertOk()
            ->assertSee('Database di Toko Online');
    }

    public function test_sorting_facts_order(): void
    {
        $student = $this->student();
        $this->actingAs($student);

        // A-Z sort
        $responseAz = $this->get('/siswa/zona-santai/database-fun-facts?sort=az');
        $responseAz->assertOk();
        $firstTitle = DatabaseFunFact::active()->orderBy('title')->first()->title;
        $responseAz->assertSee($firstTitle);
    }

    public function test_search_filters_facts_by_keyword(): void
    {
        $student = $this->student();
        $this->actingAs($student);

        $response = $this->get('/siswa/zona-santai/database-fun-facts?q=Spotify');
        $response->assertOk()
            ->assertSee('Database di Layanan Musik')
            ->assertDontSee('Database dalam Dunia Game');
    }

    public function test_detail_view_renders_educational_sections_and_records_read_progress(): void
    {
        $student = $this->student();
        $fact = DatabaseFunFact::where('slug', 'database-di-lingkungan-sekolah')->firstOrFail();

        $this->assertDatabaseMissing('database_fun_fact_reads', [
            'student_id' => $student->id,
            'database_fun_fact_id' => $fact->id,
        ]);

        $response = $this->actingAs($student)->get('/siswa/zona-santai/database-fun-facts/'.$fact->slug);

        $response->assertOk()
            ->assertSee($fact->title)
            ->assertSee('Penjelasan Lengkap')
            ->assertSee('Contoh Penerapan Nyata')
            ->assertSee('Konsep Database yang Berkaitan')
            ->assertSee('Hubungannya dengan SQL')
            ->assertSee('Status: Sudah dibaca')
            ->assertSee('Kembali ke Database Fun Facts');

        // Progress record created in database
        $this->assertDatabaseHas('database_fun_fact_reads', [
            'student_id' => $student->id,
            'database_fun_fact_id' => $fact->id,
        ]);
    }

    public function test_reading_same_fact_multiple_times_is_idempotent(): void
    {
        $student = $this->student();
        $fact = DatabaseFunFact::firstOrFail();

        $this->actingAs($student);

        // Read 1st time
        $this->get('/siswa/zona-santai/database-fun-facts/'.$fact->slug)->assertOk();
        $this->assertSame(1, DatabaseFunFactRead::where('student_id', $student->id)->where('database_fun_fact_id', $fact->id)->count());

        $firstReadAt = DatabaseFunFactRead::where('student_id', $student->id)->where('database_fun_fact_id', $fact->id)->first()->first_read_at->toDateTimeString();

        // Read 2nd time
        $this->get('/siswa/zona-santai/database-fun-facts/'.$fact->slug)->assertOk();
        $this->assertSame(1, DatabaseFunFactRead::where('student_id', $student->id)->where('database_fun_fact_id', $fact->id)->count());

        $freshRecord = DatabaseFunFactRead::where('student_id', $student->id)->where('database_fun_fact_id', $fact->id)->first();
        $this->assertSame($firstReadAt, $freshRecord->first_read_at->toDateTimeString());
    }

    public function test_exploration_progress_percentage_calculation(): void
    {
        $student = $this->student();
        $this->actingAs($student);

        $total = DatabaseFunFact::active()->count();
        $this->assertGreaterThan(0, $total);

        // Before reading any fact
        $response0 = $this->get('/siswa/zona-santai/database-fun-facts');
        $response0->assertOk()->assertSee('0%')->assertSee('0 / '.$total);

        // Read 2 facts
        $facts = DatabaseFunFact::active()->take(2)->get();
        foreach ($facts as $f) {
            $this->get('/siswa/zona-santai/database-fun-facts/'.$f->slug);
        }

        $expectedPercent = (int) round((2 / $total) * 100);
        $response2 = $this->get('/siswa/zona-santai/database-fun-facts');
        $response2->assertOk()
            ->assertSee($expectedPercent.'%')
            ->assertSee('2 / '.$total);
    }

    public function test_read_progress_is_isolated_between_students(): void
    {
        $studentA = $this->student();
        $studentB = User::where('role', 'siswa')->where('id', '!=', $studentA->id)->firstOrFail();

        $fact = DatabaseFunFact::firstOrFail();

        // Student A reads fact
        $this->actingAs($studentA)->get('/siswa/zona-santai/database-fun-facts/'.$fact->slug);

        // Student A has progress
        $this->assertTrue($fact->isReadBy($studentA));

        // Student B does NOT have progress
        $this->assertFalse($fact->isReadBy($studentB));

        // Student B sees 0%
        $this->actingAs($studentB);
        $total = DatabaseFunFact::active()->count();
        $this->get('/siswa/zona-santai/database-fun-facts')->assertSee('0 / '.$total);
    }

    public function test_recommendations_point_to_active_facts(): void
    {
        $student = $this->student();
        $response = $this->actingAs($student)->get('/siswa/zona-santai/database-fun-facts');
        $response->assertOk();

        $recommendations = $response->viewData('recommendations');
        $this->assertNotEmpty($recommendations);
        $this->assertLessThanOrEqual(3, $recommendations->count());

        foreach ($recommendations as $rec) {
            $this->assertSame('aktif', $rec->status);
        }
    }

    public function test_trivia_api_returns_verified_facts(): void
    {
        $student = $this->student();
        $response = $this->actingAs($student)->getJson(route('siswa.fun-zone.database-fun-facts.trivia'));

        $response->assertOk()
            ->assertJsonStructure(['trivia' => [['text', 'source']]]);

        $this->assertStringContainsString('2,5 quintillion bytes', $response->json('trivia.0.text'));
    }
}
