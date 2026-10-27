<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\LearningTest;
use App\Models\Project;
use App\Models\ProjectSubmission;
use App\Models\TestResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimingFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function account(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    public function test_teacher_can_set_test_challenge_and_project_timing(): void
    {
        $teacher = $this->account('guru');
        $student = $this->account('siswa');
        $this->actingAs($teacher);

        $this->post('/guru/tests', [
            'title' => 'Tes bertimer', 'type' => 'pretest', 'class_id' => $student->class_id,
            'duration_minutes' => 30, 'status' => 'aktif',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tests', ['title' => 'Tes bertimer', 'duration_minutes' => 30]);

        $material = \App\Models\Material::where('class_id', $student->class_id)->firstOrFail();
        $this->post('/guru/challenges', [
            'title' => 'Challenge bertimer', 'description' => 'Tugas SQL', 'class_id' => $student->class_id,
            'material_id' => $material->id, 'difficulty' => 'Mudah', 'duration_minutes' => 10,
            'expected_query' => 'SELECT nama FROM siswa', 'points' => 50, 'status' => 'aktif',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('challenges', ['title' => 'Challenge bertimer', 'duration_minutes' => 10]);

        $deadline = now()->addDays(3)->format('Y-m-d\\TH:i');
        $this->post('/guru/projects', [
            'title' => 'Project berdeadline', 'description' => 'Kumpulkan project', 'class_id' => $student->class_id,
            'week' => 3, 'deadline_at' => $deadline, 'allow_late_submissions' => 1,
            'status' => 'aktif', 'steps' => 'Tahap satu',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Project::where('title', 'Project berdeadline')->firstOrFail()->allow_late_submissions);
    }

    public function test_test_timer_survives_refresh_autosaves_and_locks_after_expiry(): void
    {
        $student = $this->account('siswa');
        $test = LearningTest::where('class_id', $student->class_id)->firstOrFail();
        $test->update(['duration_minutes' => 30]);
        $this->actingAs($student);
        $this->get('/siswa/tests/'.$test->id)->assertOk()->assertSee('30 menit');
        $this->post('/siswa/tests/'.$test->id.'/start')->assertRedirect();
        $result = TestResult::where('student_id', $student->id)->where('test_id', $test->id)->firstOrFail();
        $this->assertEquals(30, $result->started_at->diffInMinutes($result->ends_at));

        $this->get('/siswa/tests/'.$test->id)->assertOk()->assertSee('data-target-time="'.$result->ends_at->timestamp.'"', false);
        $question = $result->snapshot[0];
        $this->postJson('/siswa/tests/'.$test->id.'/answers', ['question_id' => $question['id'], 'answer' => $question['correct_answer']])->assertOk();
        $this->assertDatabaseHas('test_answers', ['test_result_id' => $result->id, 'question_id' => $question['id']]);

        $this->travel(31)->minutes();
        $this->postJson('/siswa/tests/'.$test->id.'/answers', ['question_id' => $question['id'], 'answer' => 'A'])->assertStatus(409);
        $this->assertNotNull($result->fresh()->submitted_at);
        $this->assertEquals(round(100 / count($result->snapshot), 2), $result->fresh()->score);
        $this->post('/siswa/tests/'.$test->id.'/submit', ['answers' => []])->assertStatus(409);

        $other = User::where('role', 'siswa')->whereKeyNot($student->id)->firstOrFail();
        $this->actingAs($other)->postJson('/siswa/tests/'.$test->id.'/answers', ['question_id' => $question['id'], 'answer' => 'A'])->assertNotFound();
    }

    public function test_challenge_timer_blocks_late_submission_and_project_late_policy_is_enforced(): void
    {
        $student = $this->account('siswa');
        $challenge = Challenge::where('class_id', $student->class_id)->firstOrFail();
        $challenge->update(['duration_minutes' => 5]);
        $this->actingAs($student)->post('/siswa/challenges/'.$challenge->id.'/start')->assertRedirect();
        $session = ChallengeSubmission::where('student_id', $student->id)->where('challenge_id', $challenge->id)->firstOrFail();
        $this->assertEquals(5, $session->started_at->diffInMinutes($session->ends_at));
        $this->get('/siswa/challenges/'.$challenge->id)->assertOk()->assertSee('data-target-time="'.$session->ends_at->timestamp.'"', false);
        $this->travel(6)->minutes();
        $this->post('/siswa/challenges/'.$challenge->id, ['query' => $challenge->expected_query])->assertSessionHasErrors('query');
        $this->assertSame('kedaluwarsa', $session->fresh()->status);
        $this->assertSame(0, $session->fresh()->score);

        $project = Project::where('class_id', $student->class_id)->firstOrFail();
        $project->update(['deadline_at' => now()->addMinute(), 'allow_late_submissions' => false]);
        $this->post('/siswa/projects/'.$project->id, ['notes' => 'Dikumpulkan tepat waktu'])->assertSessionHasNoErrors();
        $submission = ProjectSubmission::where('student_id', $student->id)->where('project_id', $project->id)->firstOrFail();
        $submission->update(['score' => 88, 'feedback' => 'Bagus']);

        $project->update(['allow_late_submissions' => true]);
        $this->travel(2)->minutes();
        $this->post('/siswa/projects/'.$project->id, ['notes' => 'Perbaikan terlambat'])->assertSessionHasNoErrors();
        $this->assertTrue($submission->fresh()->is_late);
        $this->assertEquals(88, $submission->fresh()->score);
        $this->assertSame('Bagus', $submission->fresh()->feedback);

        $project->update(['allow_late_submissions' => false]);
        $this->post('/siswa/projects/'.$project->id, ['notes' => 'Tolak terlambat'])->assertSessionHasErrors('project');
    }
}
