<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\LearningTest;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectSubmission;
use App\Models\SchoolClass;
use App\Models\TestResult;
use App\Models\User;
use App\Services\Progress;
use App\Services\SqlSandbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class DBLabTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function account($role)
    {
        return User::where('role', $role)->firstOrFail();
    }

    public function test_all_role_pages_render_and_wrong_role_is_denied(): void
    {
        $landing = $this->get('/')->assertOk();
        if (config('services.google.client_id') && config('services.google.client_secret')) {
            $landing->assertSee('Masuk dengan Google');
        } else {
            $landing->assertDontSee('Masuk dengan Google')->assertSee('name="password"', false);
        }
        $pages = ['admin' => ['dashboard', 'users', 'classes', 'years', 'assignments', 'logs', 'import'], 'guru' => ['dashboard', 'students', 'materials', 'tests', 'challenges', 'projects', 'analytics'], 'siswa' => ['dashboard', 'materials', 'pretest', 'posttest', 'lab', 'challenges', 'projects', 'progress']];
        foreach ($pages as $role => $paths) {
            $this->actingAs($this->account($role));
            foreach ($paths as $path) {
                $this->get('/'.$role.'/'.$path)->assertOk();
            }foreach (array_diff(array_keys($pages), [$role]) as $other) {
                $this->get('/'.$other.'/dashboard')->assertForbidden();
            }
        }
    }

    public function test_details_forms_and_guides_render(): void
    {
        $this->actingAs($this->account('guru'));
        $test = LearningTest::first();
        $project = Project::first();
        $this->get('/guru/tests/'.$test->id.'/questions')->assertOk();
        $this->get('/guru/tests/'.$test->id.'/questions?edit='.$test->questions->first()->id)->assertOk();
        $this->get('/guru/projects/'.$project->id.'/steps')->assertOk();
        $this->get('/guru/projects/'.$project->id.'/submissions')->assertOk();
        foreach (['materials' => Material::first(), 'tests' => $test, 'challenges' => Challenge::first(), 'projects' => $project] as $path => $obj) {
            $this->get('/guru/'.$path.'?edit='.$obj->id)->assertOk();
        }
        $this->actingAs($this->account('siswa'));
        foreach (['materials' => Material::first(), 'tests' => $test, 'challenges' => Challenge::first(), 'projects' => $project] as $path => $obj) {
            $this->get('/siswa/'.$path.'/'.$obj->id)->assertOk();
        }$this->get('/panduan')->assertOk();
    }

    public function test_student_and_teacher_cannot_access_other_classes(): void
    {
        $s = $this->account('siswa');
        $m = Material::where('class_id', '!=', $s->class_id)->first();
        $this->actingAs($s)->get('/siswa/materials/'.$m->id)->assertForbidden();
        $this->post('/siswa/materials/'.$m->id.'/complete')->assertForbidden();
        $t = $this->account('guru');
        $t->classes()->sync([$s->class_id]);
        $this->actingAs($t)->get('/guru/materials?edit='.$m->id)->assertForbidden();
        $this->delete('/guru/materials/'.$m->id)->assertForbidden();
        $this->post('/guru/materials', ['title' => 'Invalid', 'description' => 'x', 'content' => 'x', 'week' => 1, 'sort_order' => 1, 'status' => 'aktif', 'class_id' => $m->class_id])->assertSessionHasErrors('class_id');
    }

    public function test_inactive_account_cannot_use_dashboard(): void
    {
        $u = $this->account('siswa');
        $u->update(['status' => 'nonaktif']);
        $this->actingAs($u)->get('/siswa/dashboard')->assertForbidden();
    }

    public function test_admin_user_crud_persists_class_membership(): void
    {
        $this->actingAs($this->account('admin'));
        $d = ['name' => 'Test Siswa', 'email' => 'new@example.com', 'password' => 'safe-test-password', 'role' => 'siswa', 'status' => 'aktif', 'nis' => '100099', 'class_id' => SchoolClass::first()->id];
        $this->post('/admin/users', $d)->assertSessionHasNoErrors()->assertRedirect('/admin/users');
        $u = User::where('email', $d['email'])->firstOrFail();
        $this->assertDatabaseHas('class_students', ['student_id' => $u->id, 'class_id' => $d['class_id']]);
        $this->post('/admin/users/'.$u->id, array_replace($d, ['status' => 'nonaktif']))->assertSessionHasNoErrors();
        $this->assertSame('nonaktif', $u->fresh()->status);
        $this->delete('/admin/users/'.$u->id)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('users', ['id' => $u->id]);
    }

    public function test_material_upload_download_and_completion(): void
    {
        Storage::fake('supabase_private');
        $s = $this->account('siswa');
        $this->actingAs($this->account('guru'));
        $file = UploadedFile::fake()->createWithContent('materi.pdf', "%PDF-1.4\nTest PDF");
        $this->post('/guru/materials', ['title' => 'Upload Test', 'description' => 'Test', 'content' => 'Isi', 'class_id' => $s->class_id, 'week' => 6, 'sort_order' => 1, 'status' => 'aktif', 'file' => $file])->assertSessionHasNoErrors();
        $m = Material::where('title', 'Upload Test')->firstOrFail();
        Storage::disk('supabase_private')->assertExists($m->file_path);
        $this->actingAs($s)->get('/materials/'.$m->id.'/download')->assertOk();
        $this->post('/siswa/materials/'.$m->id.'/complete')->assertSessionHasNoErrors();
        $this->post('/siswa/materials/'.$m->id.'/complete');
        $this->assertDatabaseCount('material_progress', 1);
    }

    public function test_test_scoring_uses_snapshot_and_prevents_resubmit(): void
    {
        $s = $this->account('siswa');
        $test = LearningTest::where('class_id', $s->class_id)->where('type', 'pretest')->first();
        $this->actingAs($s)->post('/siswa/tests/'.$test->id.'/start')->assertRedirect();
        $this->get('/siswa/tests/'.$test->id)->assertOk()->assertDontSee('correct_answer');
        $result = TestResult::first();
        $answers = [];
        foreach ($result->snapshot as $q) {
            $answers[$q['id']] = $q['correct_answer'];
        }$test->questions()->update(['correct_answer' => 'D']);
        $this->post('/siswa/tests/'.$test->id.'/submit', ['answers' => $answers])->assertSessionHasNoErrors();
        $this->assertEquals(100, $result->fresh()->score);
        $this->post('/siswa/tests/'.$test->id.'/submit', ['answers' => $answers])->assertStatus(409);
        $this->get('/siswa/tests/'.$test->id)->assertOk()->assertSee('100');
    }

    public function test_missing_test_answer_does_not_save_partial_results(): void
    {
        $s = $this->account('siswa');
        $t = LearningTest::where('class_id', $s->class_id)->first();
        $this->actingAs($s)->post('/siswa/tests/'.$t->id.'/start');
        $this->post('/siswa/tests/'.$t->id.'/submit', ['answers' => []])->assertSessionHasErrors();
        $this->assertNull(TestResult::first()->submitted_at);
        $this->assertDatabaseCount('test_answers', 0);
    }

    public function test_challenge_awards_xp_only_once(): void
    {
        $s = $this->account('siswa');
        $c = Challenge::where('class_id', $s->class_id)->first();
        $this->actingAs($s);
        $this->post('/siswa/challenges/'.$c->id.'/start')->assertRedirect('/siswa/challenges/'.$c->id);
        $this->post('/siswa/challenges/'.$c->id, ['query' => $c->expected_query])->assertSessionHasNoErrors();
        $this->post('/siswa/challenges/'.$c->id, ['query' => $c->expected_query]);
        $this->assertDatabaseCount('challenge_submissions', 1);
        $this->assertEquals(100, ChallengeSubmission::sum('score'));
        $this->assertEquals(100, app(Progress::class)->forStudent($s)['xp']);
    }

    public function test_project_steps_submission_and_grading(): void
    {
        $s = $this->account('siswa');
        $p = Project::where('class_id', $s->class_id)->first();
        $step = $p->steps->first();
        $this->actingAs($s)->post('/siswa/steps/'.$step->id, ['complete' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('student_project_steps', ['student_id' => $s->id, 'project_step_id' => $step->id]);
        $this->post('/siswa/projects/'.$p->id, ['notes' => 'Hasil saya'])->assertSessionHasNoErrors();
        $sub = ProjectSubmission::firstOrFail();
        $this->actingAs($this->account('guru'))->post('/guru/submissions/'.$sub->id.'/grade', ['score' => 90, 'feedback' => 'Bagus'])->assertSessionHasNoErrors();
        $this->assertEquals(90, $sub->fresh()->score);
        $this->get('/guru/projects/'.$p->id.'/submissions')->assertOk();
    }

    public function test_csv_preview_and_commit_handles_invalid_rows(): void
    {
        $this->actingAs($this->account('admin'));
        $csv = "NIS,Nama,Email,Kelas\n90001,Satu,valid@example.com,XI RPL 1\n90002,Dua,bad-email,XI RPL 1\n90003,Tiga,valid@example.com,XI RPL 1\n";
        $f = UploadedFile::fake()->createWithContent('siswa.csv', $csv);
        $this->post('/admin/import/preview', ['file' => $f])->assertSessionHasNoErrors()->assertRedirect('/admin/import');
        $this->get('/admin/import')->assertOk()->assertSee('valid@example.com');
        $this->post('/admin/import/commit')->assertSessionHas('success', 'Berhasil: 1 siswa. Gagal: 2 siswa.');
        $this->assertDatabaseHas('users', ['email' => 'valid@example.com']);
    }

    public function test_xlsx_import(): void
    {
        $sheet = new Spreadsheet;
        $sheet->getActiveSheet()->fromArray([['NIS', 'Nama', 'Email', 'Kelas'], ['90009', 'Excel Siswa', 'excel@example.com', 'XI RPL 2']]);
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($sheet))->save($path);
        $f = new UploadedFile($path, 'siswa.xlsx', null, null, true);
        $this->actingAs($this->account('admin'))->post('/admin/import/preview', ['file' => $f])->assertSessionHasNoErrors();
        $this->post('/admin/import/commit')->assertSessionHas('success', 'Berhasil: 1 siswa. Gagal: 0 siswa.');
        @unlink($path);
    }

    public function test_sql_sandbox_and_feedback(): void
    {
        $sql = app(SqlSandbox::class);
        $this->assertCount(3, $sql->run("SELECT nama FROM siswa WHERE kelas='XI RPL 1'")['rows']);
        foreach (['DELETE FROM siswa', 'SELECT * FROM sqlite_master', 'SELECT load_extension(\'x\')', 'SELECT * FROM siswa; DROP TABLE siswa', 'SELECT randomblob(999999999)', 'ATTACH DATABASE \'x\' AS x'] as $q) {
            try {
                $sql->run($q);
                $this->fail('Query should be rejected: '.$q);
            } catch (\InvalidArgumentException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }$this->expectExceptionMessage('WHERE');
        $sql->run("SELECT nama FROM siswa WHARE kelas='XI RPL 1'");
    }

    public function test_sql_supports_join_and_aggregate(): void
    {
        $sql = app(SqlSandbox::class);
        $rows = $sql->run('SELECT pelanggan.nama, SUM(pesanan.total) AS total FROM pelanggan JOIN pesanan ON pelanggan.id=pesanan.pelanggan_id GROUP BY pelanggan.nama')['rows'];
        $this->assertCount(2, $rows);
        $this->assertTrue($sql->equivalent([['a' => 1], ['a' => 2]], [['b' => 2], ['b' => 1]]));
        $this->assertFalse($sql->equivalent([['a' => 1], ['a' => 1]], [['a' => 1]]));
    }

    private function google($email, $verified = true, $id = 'google-1'): void
    {
        $g = new GoogleUser;
        $g->map(['id' => $id, 'email' => $email, 'name' => 'Google Name', 'avatar' => 'https://example.com/avatar.png']);
        $g->user = ['verified_email' => $verified];
        $provider = \Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($g);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_google_rejects_unregistered_email_without_creating_user(): void
    {
        $count = User::count();
        $this->google('unregistered@example.com');
        $this->get('/auth/google/callback')->assertRedirect('/')->assertSessionHasErrors('login');
        $this->assertGuest();
        $this->assertEquals($count, User::count());
    }

    public function test_google_login_redirects_and_links_registered_user(): void
    {
        $u = $this->account('siswa');
        $this->google($u->email);
        $this->get('/auth/google/callback')->assertRedirect('/siswa/dashboard');
        $this->assertAuthenticatedAs($u);
        $this->assertSame('google-1', $u->fresh()->google_id);
    }

    public function test_google_rejects_domain_and_inactive_accounts(): void
    {
        config(['services.google.allowed_domain' => 'school.test']);
        $this->google($this->account('siswa')->email);
        $this->get('/auth/google/callback')->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_google_rejects_unverified_email(): void
    {
        $this->google($this->account('siswa')->email, false);
        $this->get('/auth/google/callback')->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_google_rejects_changed_subject(): void
    {
        $u = $this->account('siswa');
        $u->update(['google_id' => 'old-id']);
        $this->google($u->email, true, 'other-id');
        $this->get('/auth/google/callback')->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_google_state_is_validated_by_real_provider(): void
    {
        config(['services.google.client_id' => 'test', 'services.google.client_secret' => 'test', 'services.google.redirect' => 'http://localhost/auth/google/callback']);
        $this->withSession(['state' => 'expected'])->get('/auth/google/callback?state=wrong&code=x')->assertRedirect('/')->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_admin_class_year_assignment_workflow(): void
    {
        $this->actingAs($this->account('admin'));
        $this->post('/admin/years', ['name' => '2027/2028', 'semester' => 'Genap', 'active' => 1])->assertSessionHasNoErrors();
        $year = AcademicYear::where('name', '2027/2028')->firstOrFail();
        $this->assertEquals(1, AcademicYear::where('active', true)->count());
        $this->post('/admin/classes', ['name' => 'XII RPL 3', 'level' => 'XII', 'academic_year_id' => $year->id, 'status' => 'aktif'])->assertSessionHasNoErrors();
        $class = SchoolClass::where('name', 'XII RPL 3')->firstOrFail();
        $teacher = $this->account('guru');
        $this->post('/admin/assignments', ['class_id' => $class->id, 'teacher_id' => $teacher->id])->assertSessionHasNoErrors();
        $this->assertTrue($class->teachers()->where('users.id', $teacher->id)->exists());
        $this->post('/admin/assignments', ['class_id' => $class->id, 'teacher_id' => $teacher->id, 'remove' => 1])->assertSessionHasNoErrors();
        $this->delete('/admin/classes/'.$class->id)->assertSessionHasNoErrors();
        $this->delete('/admin/years/'.$year->id)->assertSessionHasNoErrors();
    }

    public function test_teacher_content_crud_and_question_changes(): void
    {
        $this->actingAs($this->account('guru'));
        $class = $this->account('siswa')->class_id;
        $this->post('/guru/tests', ['title' => 'CRUD test', 'type' => 'pretest', 'class_id' => $class, 'status' => 'aktif'])->assertSessionHasNoErrors();
        $t = LearningTest::where('title', 'CRUD test')->firstOrFail();
        $q = ['question' => 'Pertanyaan baru', 'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D', 'correct_answer' => 'B'];
        $this->post('/guru/tests/'.$t->id.'/questions', $q)->assertSessionHasNoErrors();
        $question = $t->questions()->firstOrFail();
        $this->post('/guru/tests/'.$t->id.'/questions/'.$question->id, array_replace($q, ['correct_answer' => 'A']))->assertSessionHasNoErrors();
        $this->assertSame('A', $question->fresh()->correct_answer);
        $this->delete('/guru/tests/'.$t->id.'/questions/'.$question->id)->assertSessionHasNoErrors();
        $this->delete('/guru/tests/'.$t->id)->assertSessionHasNoErrors();
        $data = ['title' => 'CRUD challenge', 'description' => 'Ambil siswa', 'class_id' => $class, 'material_id' => Material::where('class_id', $class)->first()->id, 'difficulty' => 'Mudah', 'expected_query' => 'SELECT nama FROM siswa', 'points' => 150, 'status' => 'aktif'];
        $this->post('/guru/challenges', $data)->assertSessionHasNoErrors();
        $c = Challenge::where('title', 'CRUD challenge')->firstOrFail();
        $this->assertCount(4, $c->expected_result);
        $this->post('/guru/challenges/'.$c->id, array_replace($data, ['points' => 200]))->assertSessionHasNoErrors();
        $this->delete('/guru/challenges/'.$c->id)->assertSessionHasNoErrors();
        $data = ['title' => 'CRUD project', 'description' => 'Rancang', 'class_id' => $class, 'week' => 10, 'deadline' => now()->addWeek()->toDateString(), 'status' => 'aktif', 'steps' => "Tahap A\nTahap B"];
        $this->post('/guru/projects', $data)->assertSessionHasNoErrors();
        $p = Project::where('title', 'CRUD project')->firstOrFail();
        $this->assertCount(2, $p->steps);
        $this->post('/guru/projects/'.$p->id, array_replace($data, ['title' => 'Project diperbarui']))->assertSessionHasNoErrors();
        $step = $p->steps->first();
        $this->post('/guru/projects/'.$p->id.'/steps/'.$step->id, ['title' => 'Tahap revisi', 'sort_order' => 1])->assertSessionHasNoErrors();
        $this->post('/guru/projects/'.$p->id.'/steps', ['title' => 'Presentasi', 'sort_order' => 3])->assertSessionHasNoErrors();
        $this->delete('/guru/projects/'.$p->id.'/steps/'.$step->id)->assertSessionHasNoErrors();
        $this->delete('/guru/projects/'.$p->id)->assertSessionHasNoErrors();
    }

    public function test_guide_pdf_is_downloadable_without_login(): void
    {
        $this->get('/panduan/download')->assertOk()->assertDownload('DBLab-RPL-Panduan.pdf');
    }

    public function test_inactive_google_login_rejected(): void
    {
        $u = $this->account('siswa');
        $u->update(['status' => 'nonaktif']);
        $this->google($u->email);
        $this->get('/auth/google/callback')->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_project_deadline_enforced_and_other_student_data_protected(): void
    {
        $s = $this->account('siswa');
        $p = Project::where('class_id', $s->class_id)->first();
        $p->update(['deadline' => now()->subDay()->toDateString()]);
        $this->actingAs($s)->post('/siswa/projects/'.$p->id, ['notes' => 'Terlambat'])->assertSessionHasErrors('project');
        $this->assertDatabaseCount('project_submissions', 0);
        $other = Project::where('class_id', '!=', $s->class_id)->first();
        $this->post('/siswa/steps/'.$other->steps->first()->id, ['complete' => 1])->assertForbidden();
        $this->post('/siswa/projects/'.$other->id, ['notes' => 'IDOR'])->assertForbidden();
    }

    public function test_admin_cannot_disable_or_delete_self(): void
    {
        $u = $this->account('admin');
        $this->actingAs($u)->post('/admin/users/'.$u->id,['name' => $u->name, 'email' => $u->email, 'role' => 'admin', 'status' => 'nonaktif'])->assertSessionHasErrors('status');
        $this->delete('/admin/users/'.$u->id)->assertStatus(422);
    }
}
