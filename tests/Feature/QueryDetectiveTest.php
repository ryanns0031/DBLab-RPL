<?php

namespace Tests\Feature;

use App\Models\QueryDetectiveSession;
use App\Models\User;
use App\Services\QueryDetectiveGame;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueryDetectiveTest extends TestCase
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

    public function test_query_detective_keeps_answers_hidden_and_tracks_attempts_and_score(): void
    {
        $url = '/siswa/zona-santai/query-detective';
        $this->actingAs($this->student())->get($url)->assertOk()
            ->assertSee('Query Detective')->assertSee('Kasus 1')->assertSee('dari 10')
            ->assertDontSee('wrongTokenIds')->assertDontSee('correctedQuery')->assertDontSee('WHRRE seharusnya WHERE');

        $game = app(QueryDetectiveGame::class);
        $case = $game->cases()['syntax-keywords'];
        $session = QueryDetectiveSession::firstOrFail();
        $validWrongSelection = collect(array_column($case['tokens'], 'id'))->first(fn ($id) => ! in_array($id, $case['wrongTokenIds'], true));
        $this->from($url)->post($url.'/check', ['case_id' => 'syntax-keywords', 'tokens' => [$validWrongSelection]])
            ->assertRedirect($url)->assertSessionHas('feedback');
        $this->assertSame(0, $session->fresh()->score);
        $this->assertSame(1, $session->fresh()->case_progress['syntax-keywords']['attempts']);

        $this->from($url)->post($url.'/check', ['case_id' => 'syntax-keywords', 'tokens' => $case['wrongTokenIds']])
            ->assertRedirect($url)->assertSessionHas('success');
        $this->assertSame(10, $session->fresh()->score);
        $this->get($url)->assertOk()->assertSee('Penjelasan (setelah jawaban)')
            ->assertSee('WHRRE seharusnya WHERE')->assertSee('Hasil query yang benar:')->assertSee('Budi');
        $this->post($url.'/check', ['case_id' => 'syntax-keywords', 'tokens' => $case['wrongTokenIds']])->assertSessionHas('feedback');
        $this->assertSame(10, $session->fresh()->score);
        $this->post($url.'/next')->assertRedirect($url);
        $this->get($url)->assertSee('Kasus 2')->assertSee('dari 10');
        $this->post($url.'/previous')->assertRedirect($url);
        $this->get($url)->assertSee('Kasus 1')->assertSee('dari 10');
        $this->post($url.'/next')->assertRedirect($url);
        $allCases = $game->cases();
        for ($index = 1; $index < count($session->case_order); $index++) {
            $activeCaseId = $session->fresh()->case_order[$index];
            $activeCase = $allCases[$activeCaseId];
            $this->post($url.'/check', ['case_id' => $activeCaseId, 'tokens' => $activeCase['wrongTokenIds']])->assertRedirect($url);
            $this->assertSame($index, $session->fresh()->current_index);
            $this->assertTrue($session->fresh()->case_progress[$activeCaseId]['solved'] ?? false, $activeCaseId);
            if ($index < count($session->case_order) - 1) {
                $this->post($url.'/next')->assertRedirect($url);
            }
        }
        $this->assertSame(100, $session->fresh()->score);
        $this->assertNotNull($session->fresh()->completed_at);
        $this->get($url)->assertOk()->assertSee('Hasil Query Detective')->assertSee('dari 100 poin');
    }

    public function test_all_curated_corrected_queries_match_their_expected_results(): void
    {
        foreach (app(QueryDetectiveGame::class)->cases() as $case) {
            $this->assertSame($case['results'], $this->executeQuery($case), $case['title']);
        }
    }

    public function test_game_requires_a_student_and_rejects_tokens_from_another_case(): void
    {
        $url = '/siswa/zona-santai/query-detective';
        $this->get($url)->assertRedirect('/');
        $this->actingAs(User::where('role', 'guru')->firstOrFail())->get($url)->assertForbidden();
        $this->actingAs($this->student())->get($url)->assertOk();
        $this->post($url.'/check', ['case_id' => 'not-active', 'tokens' => ['token-1']])->assertStatus(409);
        $this->post($url.'/check', ['case_id' => 'syntax-keywords', 'tokens' => ['not-a-token']])->assertStatus(422);
    }

    private function executeQuery(array $case): array
    {
        $db = new \SQLite3(':memory:');
        foreach ($case['dataset'] as $tableName => $rows) {
            $columns = $case['columns'][$tableName];
            $definitions = array_map(function ($column) use ($rows) {
                $type = is_int($rows[0][$column]) ? 'INTEGER' : 'TEXT';
                return '"'.$column.'" '.$type;
            }, $columns);
            $db->exec('CREATE TABLE "'.$tableName.'" ('.implode(', ', $definitions).')');
            foreach ($rows as $row) {
                $statement = $db->prepare('INSERT INTO "'.$tableName.'" ('.implode(', ', array_map(fn ($column) => '"'.$column.'"', $columns)).') VALUES ('.implode(', ', array_fill(0, count($columns), '?')).')');
                foreach ($columns as $index => $column) {
                    $statement->bindValue($index + 1, $row[$column], is_int($row[$column]) ? SQLITE3_INTEGER : SQLITE3_TEXT);
                }
                $statement->execute();
            }
        }

        $result = $db->query($case['correctedQuery']);
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        $db->close();

        return $rows;
    }
}
