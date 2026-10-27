<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\LearningTest;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectSubmission;
use App\Models\TestResult;
use App\Services\Audit;
use App\Services\SqlSandbox;
use App\Services\YouTubeVideoUrl;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeacherController extends Controller
{
    public function definition($kind)
    {
        return match ($kind) {
            'materials' => [Material::class, 'Materi', ['title' => 'Judul', 'description' => 'Deskripsi', 'content' => 'Isi materi', 'youtube_url' => 'Link Video YouTube', 'class_id' => 'Kelas', 'week' => 'Minggu', 'sort_order' => 'Urutan', 'status' => 'Status', 'file' => 'File PDF / DOCX']],
            'tests' => [LearningTest::class, 'Pre/Post Test', ['title' => 'Judul', 'class_id' => 'Kelas', 'type' => 'Jenis tes', 'duration_minutes' => 'Durasi pengerjaan (menit, kosongkan untuk tanpa batas waktu)', 'status' => 'Status']],
            'challenges' => [Challenge::class, 'Challenge', ['title' => 'Judul', 'description' => 'Tugas', 'class_id' => 'Kelas', 'material_id' => 'Materi', 'difficulty' => 'Kesulitan', 'duration_minutes' => 'Batas waktu (menit, kosongkan untuk tanpa batas waktu)', 'expected_query' => 'Query jawaban (khusus guru)', 'points' => 'XP', 'status' => 'Status']],
            'projects' => [Project::class, 'Project', ['title' => 'Judul', 'description' => 'Deskripsi', 'class_id' => 'Kelas', 'week' => 'Minggu', 'deadline_at' => 'Tanggal dan waktu deadline', 'allow_late_submissions' => 'Izinkan pengumpulan terlambat', 'status' => 'Status', 'steps' => 'Tahapan (satu per baris)']],default => abort(404)
        };
    }

    public function index(Request $r, string $kind)
    {
        [$model,$title,$fields] = $this->definition($kind);
        $ids = $r->user()->classes()->pluck('classes.id');
        $rows = $model::whereIn('class_id', $ids)->latest()->paginate(15);
        $edit = $r->filled('edit') ? $model::findOrFail($r->integer('edit')) : null;
        if ($edit) {
            Gate::authorize('manage', $edit);
        }

return view('teacher.manage', compact('kind', 'title', 'fields', 'rows', 'edit') + ['classes' => $r->user()->classes, 'materials' => Material::whereIn('class_id', $ids)->get()]);
    }

    public function save(Request $r, string $kind, ?int $id = null)
    {
        [$model] = $this->definition($kind);
        $item = $id ? $model::findOrFail($id) : new $model;
        if ($id) {
            Gate::authorize('manage', $item);
        }$ids = $r->user()->classes()->pluck('classes.id')->all();
        $rules = ['title' => 'required|string|max:190', 'class_id' => ['required', Rule::in($ids)], 'status' => 'required|in:aktif,nonaktif'];
        $rules += match ($kind) {
            'materials' => ['description' => 'required|string|max:2000', 'content' => 'required|string|max:100000', 'youtube_url' => ['nullable', 'string', 'max:2048', 'url', function ($attribute, $value, $fail) {
                if ($value !== null && $value !== '' && YouTubeVideoUrl::videoId($value) === null) {
                    $fail('Masukkan URL YouTube HTTPS yang valid (youtube.com atau youtu.be).');
                }
            }], 'week' => 'required|integer|min:1|max:52', 'sort_order' => 'required|integer|min:1|max:999', 'file' => 'nullable|file|mimes:pdf,docx|max:10240'],
            'tests' => ['type' => 'required|in:pretest,posttest', 'duration_minutes' => 'nullable|integer|min:1|max:480'],
            'challenges' => ['description' => 'required|string|max:5000', 'material_id' => ['required', Rule::exists('materials', 'id')->where('class_id', $r->input('class_id'))], 'difficulty' => 'required|in:Mudah,Menengah,Sulit', 'duration_minutes' => 'nullable|integer|min:1|max:480', 'expected_query' => 'required|string|max:4000', 'points' => 'required|integer|min:1|max:1000'],
            'projects' => ['description' => 'required|string|max:5000', 'week' => 'required|integer|min:1|max:52', 'deadline_at' => 'required_without:deadline|nullable|date_format:Y-m-d\\TH:i', 'deadline' => 'required_without:deadline_at|nullable|date_format:Y-m-d', 'allow_late_submissions' => 'sometimes|boolean', 'steps' => ($id ? 'nullable' : 'required').'|string|max:3000']
        };
        $d = $r->validate($rules);
        if ($id && (int) $d['class_id'] !== $item->class_id) {
            return back()->withErrors(['class_id' => 'Kelas konten yang sudah dibuat tidak dapat diubah.']);
        }
        if ($kind === 'challenges') {
            try {
                $d['expected_result'] = app(SqlSandbox::class)->run($d['expected_query'])['rows'];
            } catch (\InvalidArgumentException $e) {
                return back()->withInput()->withErrors(['expected_query' => $e->getMessage()]);
            }
        }
        if ($kind === 'projects') {
            $deadlineAt = ! empty($d['deadline_at'])
                ? Carbon::createFromFormat('Y-m-d\\TH:i', $d['deadline_at'], config('app.timezone'))
                : Carbon::parse($d['deadline'], config('app.timezone'))->endOfDay();
            $d['deadline'] = $deadlineAt->toDateString();
            $d['deadline_at'] = $deadlineAt;
            $d['allow_late_submissions'] = $r->boolean('allow_late_submissions');
        }
        if ($kind === 'tests' && $id && $item->type !== $d['type'] && TestResult::where('test_id', $id)->exists()) {
            return back()->withErrors(['type' => 'Jenis tes yang sudah dikerjakan tidak dapat diubah.']);
        }
        $steps = isset($d['steps']) ? array_values(array_filter(array_map('trim', explode("\n", $d['steps'])))) : [];
        foreach ($steps as $step) {
            if (mb_strlen($step) > 190) {
                return back()->withErrors(['steps' => 'Setiap tahap maksimal 190 karakter.']);
            }
        }
        unset($d['steps'],$d['file']);
        $old = $item->file_path;
        $path = null;
        if ($kind === 'materials' && $r->hasFile('file')) {
            $path = $r->file('file')->store('materials', 'local');
            $d['file_path'] = $path;
            $d['original_name'] = $r->file('file')->getClientOriginalName();
        }
        try {
            DB::transaction(function () use ($d, $item, $kind, $steps, $id) {
                $item->fill($d);
                if (! $id) {
                    $item->teacher_id = auth()->id();
                }$item->save();
                if ($kind === 'projects' && $steps) {
                    if (! $id) {
                        foreach ($steps as $i => $s) {
                            $item->steps()->create(['title' => $s, 'sort_order' => $i + 1]);
                        }
                    } else {
                        throw_if($steps !== $item->steps->pluck('title')->all(), ValidationException::withMessages(['steps' => 'Tahap yang sudah dibuat dikelola melalui tombol Tahapan.']));
                    }
                }Audit::log('Pembelajaran disimpan: '.$item->title);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }
        if ($path && $old) {
            Storage::disk('local')->delete($old);
        }

return redirect('/guru/'.$kind)->with('success', 'Data berhasil disimpan.');
    }

    public function delete(string $kind, int $id)
    {
        [$model] = $this->definition($kind);
        $item = $model::findOrFail($id);
        Gate::authorize('manage', $item);
        try {
            $path = $item->file_path;
            $item->delete();
            if ($path) {
                Storage::disk('local')->delete($path);
            }
        } catch (QueryException $e) {
            return back()->withErrors(['delete' => 'Materi masih digunakan oleh challenge. Nonaktifkan atau hapus challenge terlebih dahulu.']);
        }Audit::log('Pembelajaran dihapus: '.$item->title);

        return back()->with('success', 'Data dihapus.');
    }

    public function questions(Request $r, LearningTest $test)
    {
        Gate::authorize('manage', $test);
        $edit = $r->filled('edit') ? $test->questions()->findOrFail($r->integer('edit')) : null;

        return view('teacher.questions', compact('test', 'edit'));
    }

    public function saveQuestion(Request $r, LearningTest $test, ?int $id = null)
    {
        Gate::authorize('manage', $test);
        $d = $r->validate(['question' => 'required|string|max:5000', 'option_a' => 'required|string|max:1000', 'option_b' => 'required|string|max:1000', 'option_c' => 'required|string|max:1000', 'option_d' => 'required|string|max:1000', 'correct_answer' => 'required|in:A,B,C,D']);
        $id ? $test->questions()->findOrFail($id)->update($d) : $test->questions()->create($d);

        return redirect('/guru/tests/'.$test->id.'/questions')->with('success', 'Soal disimpan.');
    }

    public function deleteQuestion(LearningTest $test, int $id)
    {
        Gate::authorize('manage', $test);
        $test->questions()->findOrFail($id)->delete();

        return back()->with('success', 'Soal dihapus.');
    }

    public function steps(Request $r, Project $project)
    {
        Gate::authorize('manage', $project);

        return view('teacher.steps', compact('project'));
    }

    public function saveStep(Request $r, Project $project, ?int $id = null)
    {
        Gate::authorize('manage', $project);
        $d = $r->validate(['title' => 'required|string|max:190', 'sort_order' => 'required|integer|min:1|max:100']);
        $id ? $project->steps()->findOrFail($id)->update($d) : $project->steps()->create($d);

        return back()->with('success', 'Tahapan disimpan.');
    }

    public function deleteStep(Project $project, int $id)
    {
        Gate::authorize('manage', $project);
        $project->steps()->findOrFail($id)->delete();

        return back()->with('success', 'Tahapan dihapus.');
    }

    public function submissions(Project $project)
    {
        Gate::authorize('manage', $project);

        return view('teacher.submissions', ['project' => $project, 'submissions' => ProjectSubmission::with('student')->where('project_id', $project->id)->get()]);
    }

    public function grade(Request $r, ProjectSubmission $submission)
    {
        Gate::authorize('manage', $submission->project);
        $submission->update($r->validate(['score' => 'required|integer|min:0|max:100', 'feedback' => 'nullable|string|max:2000']));

        return back()->with('success', 'Nilai project disimpan.');
    }

    public function download(ProjectSubmission $submission)
    {
        Gate::authorize('manage', $submission->project);
        abort_unless($submission->file_path && Storage::disk('local')->exists($submission->file_path),404);

        return Storage::disk('local')->download($submission->file_path,$submission->original_name,['X-Content-Type-Options' => 'nosniff']);
    }
}
