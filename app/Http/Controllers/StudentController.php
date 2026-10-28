<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\LearningTest;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\Project;
use App\Models\ProjectStep;
use App\Models\ProjectSubmission;
use App\Models\StudentProjectStep;
use App\Models\TestAnswer;
use App\Models\TestResult;
use App\Models\User;
use App\Services\Audit;
use App\Services\Progress;
use App\Services\SqlSandbox;
use App\Services\YouTubeVideoUrl;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    public function listing(Request $r, string $kind)
    {
        $model = match ($kind) {
            'materials' => Material::class,'challenges' => Challenge::class,'projects' => Project::class,'pretest','posttest' => LearningTest::class,default => abort(404)
        };
        $q = $model::where('class_id', $r->user()->class_id)->where('status', 'aktif');
        if (in_array($kind, ['pretest', 'posttest'])) {
            $q->where('type', $kind)->withCount('questions');
        }

return view('student.list', ['kind' => $kind, 'rows' => $r->user()->schoolClass?->status === 'aktif' ? ($kind === 'materials' ? $q->orderBy('week')->orderBy('sort_order') : $q)->get() : collect()]);
    }

    public function material(Material $material)
    {
        Gate::authorize('view', $material);

        return view('student.material', [
            'material' => $material,
            'videoEmbedUrl' => YouTubeVideoUrl::embedUrl($material->youtube_url),
        ]);
    }

    public function download(Material $material)
    {
        Gate::authorize('view', $material);

        if ($material->file_path && Storage::disk('supabase_private')->exists($material->file_path)) {
            return Storage::disk('supabase_private')->download($material->file_path, $material->original_name, ['X-Content-Type-Options' => 'nosniff']);
        }

        // Fallback ke local disk jika file belum termigrasi
        abort_unless($material->file_path && Storage::disk('local')->exists($material->file_path), 404);

        return Storage::disk('local')->download($material->file_path, $material->original_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function complete(Material $material)
    {
        Gate::authorize('view', $material);
        MaterialProgress::firstOrCreate(['student_id' => auth()->id(), 'material_id' => $material->id]);
        Audit::log('Materi dipelajari: '.$material->title);

        return back()->with('success', 'Progress materi disimpan.');
    }

    public function test(LearningTest $test)
    {
        Gate::authorize('view', $test);
        $result = TestResult::where('student_id', auth()->id())->where('test_id', $test->id)->first();
        if ($result?->ends_at && ! $result->submitted_at && now()->greaterThanOrEqualTo($result->ends_at)) {
            $this->finalizeExpiredTest($result->id, $test->title);
            $result->refresh();
        }

        $savedAnswers = $result
            ? TestAnswer::where('test_result_id', $result->id)->pluck('answer', 'question_id')
            : collect();

        return view('student.test', compact('test', 'result', 'savedAnswers'));
    }

    public function start(LearningTest $test)
    {
        Gate::authorize('view', $test);
        if (! $test->questions()->exists()) {
            return back()->withErrors(['test' => 'Guru belum menambahkan soal.']);
        }
        $startedAt = now();
        TestResult::firstOrCreate(
            ['student_id' => auth()->id(), 'test_id' => $test->id],
            [
                'snapshot' => $test->questions->toArray(),
                'started_at' => $startedAt,
                'ends_at' => $test->duration_minutes ? $startedAt->copy()->addMinutes($test->duration_minutes) : null,
            ],
        );

        return back();
    }

    public function saveTestAnswer(Request $r, LearningTest $test)
    {
        Gate::authorize('view', $test);
        $data = $r->validate(['question_id' => 'required|integer|min:1', 'answer' => 'required|in:A,B,C,D']);
        $expired = false;
        $saved = false;

        DB::transaction(function () use ($test, $data, &$expired, &$saved) {
            $result = TestResult::where('student_id', auth()->id())->where('test_id', $test->id)->lockForUpdate()->firstOrFail();
            abort_if($result->submitted_at, 409, 'Tes sudah dikumpulkan.');

            if ($result->ends_at && now()->greaterThanOrEqualTo($result->ends_at)) {
                $this->finalizeLockedTest($result, $test->title);
                $expired = true;

                return;
            }

            $question = collect($result->snapshot)->first(fn ($item) => (int) $item['id'] === (int) $data['question_id']);
            abort_unless($question, 404);

            TestAnswer::updateOrCreate(
                ['test_result_id' => $result->id, 'question_id' => $question['id']],
                ['answer' => $data['answer'], 'correct' => $data['answer'] === $question['correct_answer']],
            );
            $saved = true;
        });

        if ($expired) {
            return response()->json(['message' => 'Waktu tes telah habis. Jawaban terakhir yang tersimpan sudah dinilai.'], 409);
        }

        return response()->json(['saved' => $saved]);
    }

    public function submitTest(Request $r, LearningTest $test)
    {
        Gate::authorize('view', $test);
        $expired = false;
        DB::transaction(function () use ($r, $test, &$expired) {
            $result = TestResult::where('student_id', auth()->id())->where('test_id', $test->id)->lockForUpdate()->firstOrFail();
            abort_if($result->submitted_at, 409, 'Tes sudah dikumpulkan.');

            if ($result->ends_at && now()->greaterThanOrEqualTo($result->ends_at)) {
                $this->finalizeLockedTest($result, $test->title);
                $expired = true;

                return;
            }

            $rules = [];
            foreach ($result->snapshot as $q) {
                $rules['answers.'.$q['id']] = 'required|in:A,B,C,D';
            }$r->validate($rules);
            $correct = 0;
            foreach ($result->snapshot as $q) {
                $answer = $r->input('answers.'.$q['id']);
                $yes = $answer === $q['correct_answer'];
                $correct += $yes;
                TestAnswer::updateOrCreate(['test_result_id' => $result->id, 'question_id' => $q['id']], ['answer' => $answer, 'correct' => $yes]);
            }$result->update(['score' => round(100 * $correct / count($result->snapshot), 2), 'submitted_at' => now()]);
            Audit::log('Tes diselesaikan: '.$test->title);
        });

        return back()->with('success', $expired ? 'Waktu tes berakhir. Jawaban terakhir yang tersimpan telah dinilai.' : 'Jawaban disimpan dan nilai telah dihitung.');
    }

    private function finalizeExpiredTest(int $resultId, string $testTitle): void
    {
        DB::transaction(function () use ($resultId, $testTitle) {
            $result = TestResult::whereKey($resultId)->lockForUpdate()->firstOrFail();
            if (! $result->submitted_at && $result->ends_at && now()->greaterThanOrEqualTo($result->ends_at)) {
                $this->finalizeLockedTest($result, $testTitle);
            }
        });
    }

    private function finalizeLockedTest(TestResult $result, string $testTitle): void
    {
        $savedAnswers = TestAnswer::where('test_result_id', $result->id)->get()->keyBy('question_id');
        $correct = 0;
        foreach ($result->snapshot as $question) {
            $correct += ($savedAnswers->get($question['id'])?->answer === $question['correct_answer']) ? 1 : 0;
        }
        $result->update([
            'score' => $result->snapshot ? round(100 * $correct / count($result->snapshot), 2) : 0,
            'submitted_at' => now(),
        ]);
        Audit::log('Tes berakhir karena waktu habis: '.$testTitle);
    }

    public function lab()
    {
        return view('student.lab');
    }

    public function run(Request $r, SqlSandbox $sql)
    {
        $d = $r->validate(['query' => 'required|string|max:4000']);
        try {
            return response()->json($sql->run($d['query']));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function challenge(Challenge $challenge)
    {
        Gate::authorize('view', $challenge);
        $submission = ChallengeSubmission::where('student_id', auth()->id())->where('challenge_id', $challenge->id)->first();
        if ($submission?->status === 'berlangsung' && $submission->ends_at && now()->greaterThanOrEqualTo($submission->ends_at)) {
            $submission->update(['status' => 'kedaluwarsa']);
            $submission->refresh();
        }

        return view('student.challenge', compact('challenge', 'submission'));
    }

    public function startChallenge(Challenge $challenge)
    {
        Gate::authorize('view', $challenge);
        DB::transaction(function () use ($challenge) {
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            $submission = ChallengeSubmission::where('student_id', auth()->id())
                ->where('challenge_id', $challenge->id)->lockForUpdate()->first();

            if ($submission?->status === 'selesai') {
                return;
            }
            if ($submission?->status === 'berlangsung' && (! $submission->ends_at || now()->lt($submission->ends_at))) {
                return;
            }

            $startedAt = now();
            $submission ??= new ChallengeSubmission([
                'student_id' => auth()->id(),
                'challenge_id' => $challenge->id,
                'query' => '',
                'attempt' => 0,
            ]);
            $submission->fill([
                'status' => 'berlangsung',
                'score' => 0,
                'started_at' => $startedAt,
                'ends_at' => $challenge->duration_minutes ? $startedAt->copy()->addMinutes($challenge->duration_minutes) : null,
                'completed_at' => null,
            ])->save();
        });

        return redirect('/siswa/challenges/'.$challenge->id);
    }

    public function expireChallenge(Challenge $challenge)
    {
        Gate::authorize('view', $challenge);
        $data = request()->validate(['query' => 'nullable|string|max:4000']);
        $submission = ChallengeSubmission::where('student_id', auth()->id())->where('challenge_id', $challenge->id)->firstOrFail();
        abort_unless($submission->status === 'berlangsung' && $submission->ends_at && now()->greaterThanOrEqualTo($submission->ends_at), 409, 'Challenge masih berlangsung.');
        $submission->update(['status' => 'kedaluwarsa', 'query' => $data['query'] ?? $submission->query]);

        return response()->json(['expired' => true]);
    }

    public function submitChallenge(Request $r, Challenge $challenge, SqlSandbox $sql)
    {
        Gate::authorize('view', $challenge);
        $submission = ChallengeSubmission::where('student_id', auth()->id())->where('challenge_id', $challenge->id)->firstOrFail();
        if ($submission->status !== 'berlangsung' || ($submission->ends_at && now()->greaterThanOrEqualTo($submission->ends_at))) {
            if ($submission->status === 'berlangsung' && $submission->ends_at && now()->greaterThanOrEqualTo($submission->ends_at)) {
                $submission->update(['status' => 'kedaluwarsa']);
            }

            return back()->withErrors(['query' => 'Waktu challenge telah berakhir atau sesi belum dimulai. Mulai sesi baru untuk mencoba lagi.']);
        }
        $d = $r->validate(['query' => 'required|string|max:4000']);
        try {
            $rows = $sql->run($d['query'])['rows'];
            $ok = $sql->equivalent($rows, $challenge->expected_result);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['query' => $e->getMessage()]);
        }
        $expired = false;
        DB::transaction(function () use ($d, $challenge, $ok, &$expired) {
            User::whereKey(auth()->id())->lockForUpdate()->first();
            $s = ChallengeSubmission::where('student_id', auth()->id())->where('challenge_id', $challenge->id)->lockForUpdate()->firstOrFail();
            if ($s->status === 'selesai') {
                return;
            }
            if ($s->status !== 'berlangsung' || ($s->ends_at && now()->greaterThanOrEqualTo($s->ends_at))) {
                if ($s->status === 'berlangsung') {
                    $s->update(['status' => 'kedaluwarsa']);
                }
                $expired = true;

                return;
            }
            $s->fill(['query' => $d['query'], 'status' => $ok ? 'selesai' : 'berlangsung', 'score' => $ok ? $challenge->points : 0, 'attempt' => $s->attempt + 1, 'completed_at' => $ok ? now() : null])->save();
            Audit::log('Challenge '.($ok ? 'selesai' : 'dicoba').': '.$challenge->title);
        });

        if ($expired) {
            return back()->withErrors(['query' => 'Waktu challenge habis sebelum jawaban selesai diperiksa. Mulai sesi baru untuk mencoba lagi.']);
        }

        return back()->withInput()->with($ok ? 'success' : 'feedback', $ok ? 'Challenge selesai! +'.$challenge->points.' XP (diberikan satu kali).' : 'Query berjalan, tetapi hasil belum sesuai. Periksa kolom dan kondisi WHERE.');
    }

    public function project(Project $project)
    {
        Gate::authorize('view', $project);
        $deadlineAt = $project->deadline_at ?? Carbon::parse($project->deadline)->endOfDay();

        return view('student.project', [
            'project' => $project,
            'deadlineAt' => $deadlineAt,
            'canSubmitProject' => ! now()->greaterThanOrEqualTo($deadlineAt) || $project->allow_late_submissions,
            'completed' => StudentProjectStep::where('student_id', auth()->id())->pluck('project_step_id')->all(),
            'submission' => ProjectSubmission::where('student_id', auth()->id())->where('project_id', $project->id)->first(),
        ]);
    }

    public function step(Request $r, ProjectStep $step)
    {
        Gate::authorize('view', $step->project);
        $key = ['student_id' => auth()->id(), 'project_step_id' => $step->id];
        $r->boolean('complete') ? StudentProjectStep::firstOrCreate($key) : StudentProjectStep::where($key)->delete();

        return back()->with('success', 'Progress project diperbarui.');
    }

    public function submitProject(Request $r, Project $project)
    {
        Gate::authorize('view', $project);
        $data = $r->validate(['notes' => 'required|string|max:10000', 'file' => 'nullable|file|mimes:pdf,zip,txt|max:10240']);
        $filePath = $r->hasFile('file') ? $r->file('file')->store('projects', 'supabase_private') : null;
        $originalName = $r->hasFile('file') ? $r->file('file')->getClientOriginalName() : null;
        $oldFilePath = null;
        $isLate = false;

        try {
            DB::transaction(function () use ($project, $data, $filePath, $originalName, &$oldFilePath, &$isLate) {
                $lockedProject = Project::whereKey($project->id)->lockForUpdate()->firstOrFail();
                $deadlineAt = $lockedProject->deadline_at ?? Carbon::parse($lockedProject->deadline)->endOfDay();
                $isLate = now()->greaterThanOrEqualTo($deadlineAt);
                if ($isLate && ! $lockedProject->allow_late_submissions) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['project' => 'Deadline project telah berakhir. Guru tidak mengizinkan pengumpulan terlambat.']);
                }

                $submission = ProjectSubmission::where('student_id', auth()->id())
                    ->where('project_id', $lockedProject->id)->lockForUpdate()->first();
                $submission ??= new ProjectSubmission(['student_id' => auth()->id(), 'project_id' => $lockedProject->id]);
                $oldFilePath = $submission->file_path;
                $submission->notes = $data['notes'];
                $submission->is_late = $isLate;
                if ($filePath) {
                    $submission->file_path = $filePath;
                    $submission->original_name = $originalName;
                }
                $submission->save();
                Audit::log('Project dikumpulkan'.($isLate ? ' terlambat' : '').': '.$lockedProject->title);
            });
        } catch (\Throwable $e) {
            if ($filePath) {
                Storage::disk('supabase_private')->delete($filePath);
            }
            throw $e;
        }

        if ($filePath && $oldFilePath && $oldFilePath !== $filePath) {
            if (Storage::disk('supabase_private')->exists($oldFilePath)) {
                Storage::disk('supabase_private')->delete($oldFilePath);
            } elseif (Storage::disk('local')->exists($oldFilePath)) {
                Storage::disk('local')->delete($oldFilePath);
            }
        }

        return back()->with('success', $isLate ? 'Project berhasil dikumpulkan dan ditandai terlambat.' : 'Project berhasil dikumpulkan.');
    }

    public function progress(Progress $p)
    {
        return view('student.progress',['progress' => $p->forStudent(auth()->user())]);
    }
}
