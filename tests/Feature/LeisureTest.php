<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\QueryGuessSession;
use App\Services\QueryGuessGame;
use App\Services\SqlSandbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeisureTest extends TestCase
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

    public function test_zone_and_each_activity_are_only_available_to_students(): void
    {
        $this->get('/siswa/zona-santai')->assertRedirect('/');
        $student = $this->student();
        $this->actingAs($student)->get('/siswa/zona-santai')
            ->assertOk()
            ->assertSeeInOrder(['Progress', 'Zona Santai'])
            ->assertSee('Karya dipamerkan')
            ->assertSee('/assets/tebak-hasil-query.png')
            ->assertSee('/assets/query-detective.png')
            ->assertSee('/assets/database-fun-facts.png')
            ->assertSee('/assets/ruang-diskusi.png')
            ->assertSee('/assets/pameran-karya.png')
            ->assertDontSee('Database Escape Room');

        foreach (['tebak-query', 'query-detective', 'database-fun-facts', 'database-escape-room', 'pameran-karya', 'mini-games-sql'] as $activity) {
            $this->get('/siswa/zona-santai/'.$activity)->assertOk();
        }

        $this->actingAs(User::where('role', 'guru')->firstOrFail())
            ->get('/siswa/zona-santai')->assertForbidden();
    }

    public function test_query_guess_session_tracks_answers_once_and_finishes_with_a_summary(): void
    {
        $this->actingAs($this->student());
        $url = '/siswa/zona-santai/tebak-query';
        $game = app(QueryGuessGame::class);
        $this->get($url)->assertOk()->assertSee('Pertanyaan 1')->assertSee('Salin')->assertSee('Petunjuk')->assertSee('Riwayat Jawaban');
        $session = QueryGuessSession::firstOrFail();
        $questionId = $session->question_order[0];
        $question = $game->question($questionId);
        $wrongChoice = collect(array_keys($question['options']))->first(fn ($choice) => $choice !== $question['answer']);

        $this->from($url)->post($url.'/answer', ['question_id' => $questionId, 'choice' => $wrongChoice])->assertRedirect($url);
        $this->assertSame(0, $session->fresh()->score);
        $this->assertSame(1, $session->fresh()->wrong_count);
        $this->from($url)->post($url.'/answer', ['question_id' => $questionId, 'choice' => $question['answer']])
            ->assertRedirect($url)->assertSessionHas('feedback');
        $this->assertSame(0, $session->fresh()->score);
        $this->post($url.'/next')->assertRedirect($url);

        for ($index = 1; $index < 10; $index++) {
            $session->refresh();
            $questionId = $session->question_order[$index];
            $question = $game->question($questionId);
            $this->from($url)->post($url.'/answer', ['question_id' => $questionId, 'choice' => $question['answer']])->assertRedirect($url);
            if ($index < 9) {
                $this->post($url.'/next')->assertRedirect($url);
            }
        }

        $session->refresh();
        $this->assertSame(90, $session->score);
        $this->assertSame(9, $session->correct_count);
        $this->assertSame(1, $session->wrong_count);
        $this->assertNotNull($session->completed_at);
        $this->get($url)->assertOk()->assertSee('PERMAINAN SELESAI')->assertSee('90')->assertSee('tidak menambah XP challenge');
        $this->post($url.'/new')->assertRedirect($url);
        $active = QueryGuessSession::whereNull('completed_at')->firstOrFail();
        $this->assertSame([], $active->answers);
        $this->assertSame(90, $session->fresh()->score);
        $this->assertDatabaseCount('query_guess_sessions', 2);
        $this->get($url)->assertSee('data-confirm="Reset permainan ini? Riwayat sesi yang belum selesai akan dihapus."', false);
        $this->post($url.'/reset')->assertRedirect($url);
        $resetSession = QueryGuessSession::whereNull('completed_at')->firstOrFail();
        $this->assertSame([], $resetSession->answers);
        $this->assertSame(90, $session->fresh()->score);
        $this->assertDatabaseCount('query_guess_sessions', 2);
    }

    public function test_query_guess_answer_is_hidden_before_submission_and_sessions_belong_to_each_student(): void
    {
        $student = $this->student();
        $this->actingAs($student)->get('/siswa/zona-santai/tebak-query')->assertOk()->assertDontSee('correctOption');
        $firstSession = QueryGuessSession::where('student_id', $student->id)->firstOrFail();
        $other = User::where('role', 'siswa')->where('id', '!=', $student->id)->first();
        if (! $other) {
            $other = User::factory()->create(['role' => 'siswa', 'status' => 'aktif', 'class_id' => $student->class_id]);
        }
        $this->actingAs($other)->get('/siswa/zona-santai/tebak-query')->assertOk();
        $this->assertDatabaseHas('query_guess_sessions', ['student_id' => $student->id]);
        $this->assertDatabaseHas('query_guess_sessions', ['student_id' => $other->id]);
        $this->assertNotSame($firstSession->id, QueryGuessSession::where('student_id', $other->id)->firstOrFail()->id);
    }

    public function test_question_bank_has_ten_options_and_varied_correct_answer_positions(): void
    {
        $questions = app(QueryGuessGame::class)->questions();
        $this->assertCount(10, $questions);
        $positions = [];
        foreach ($questions as $question) {
            $this->assertCount(4, $question['options']);
            $this->assertArrayHasKey($question['answer'], $question['options']);
            $this->assertCount(4, array_unique(array_map(fn ($rows) => json_encode($rows), $question['options'])));
            $this->assertTrue(app(SqlSandbox::class)->equivalent($this->executeQuestion($question), $question['options'][$question['answer']]));
            $positions[] = $question['answer'];
        }
        $this->assertGreaterThan(1, count(array_unique($positions)));
    }

    private function executeQuestion(array $question): array
    {
        $db = new \SQLite3(':memory:');
        if (array_is_list($question['columns'])) {
            $tables = ['siswa' => ['columns' => $question['columns'], 'rows' => $question['dataset']]];
        } else {
            $tables = [];
            foreach ($question['dataset'] as $name => $rows) {
                $tables[$name] = ['columns' => $question['columns'][$name], 'rows' => $rows];
            }
        }

        foreach ($tables as $name => $table) {
            $definitions = [];
            foreach ($table['columns'] as $column) {
                $sample = $table['rows'][0][$column] ?? '';
                $type = is_int($sample) ? 'INTEGER' : 'TEXT';
                $definitions[] = '"'.$column.'" '.$type;
            }
            $db->exec('CREATE TABLE "'.$name.'" ('.implode(', ', $definitions).')');
            foreach ($table['rows'] as $row) {
                $columns = $table['columns'];
                $statement = $db->prepare('INSERT INTO "'.$name.'" ('.implode(', ', array_map(fn ($column) => '"'.$column.'"', $columns)).') VALUES ('.implode(', ', array_fill(0, count($columns), '?')).')');
                foreach ($columns as $index => $column) {
                    $statement->bindValue($index + 1, $row[$column], is_int($row[$column]) ? SQLITE3_INTEGER : SQLITE3_TEXT);
                }
                $statement->execute();
            }
        }

        $result = $db->query($question['query']);
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        $db->close();

        return $rows;
    }
}
