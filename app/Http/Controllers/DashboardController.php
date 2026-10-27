<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\LearningTest;
use App\Models\Project;
use App\Models\ProjectSubmission;
use App\Models\SchoolClass;
use App\Models\TestResult;
use App\Models\User;
use App\Services\Progress;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $r, Progress $p)
    {
        $u = $r->user();
        $classIds = match ($u->role) {
            'guru' => $u->classes()->pluck('classes.id'),
            'admin' => SchoolClass::where('status', 'aktif')->pluck('id'),
            default => collect([$u->class_id]),
        };
        $year = AcademicYear::where('active', true)->first();
        $students = $u->role === 'guru'
            ? User::where('role', 'siswa')->whereIn('class_id', $classIds)->get()
            : collect();
        $stats = $students->map(fn ($s) => $p->forStudent($s));
        $materials = Material::whereIn('class_id', $classIds)->where('status', 'aktif')->orderBy('week')->orderBy('sort_order')->get();
        $completedMaterialIds = $u->role === 'siswa'
            ? MaterialProgress::where('student_id', $u->id)->whereIn('material_id', $materials->pluck('id'))->pluck('material_id')->all()
            : [];
        $currentMaterial = $u->role === 'siswa'
            ? $materials->first(fn ($material) => ! in_array($material->id, $completedMaterialIds, true))
            : null;
        $timelineStart = $currentMaterial
            ? max(0, (int) $materials->search(fn ($material) => $material->id === $currentMaterial->id) - 1)
            : max(0, $materials->count() - 3);
        $dashboardLessons = $materials->slice($timelineStart, 3)->values()->map(function ($material) use ($completedMaterialIds, $currentMaterial) {
            $completed = in_array($material->id, $completedMaterialIds, true);
            $current = ! $completed && $currentMaterial?->id === $material->id;

            return [
                'material' => $material,
                'status' => $completed ? 'completed' : ($current ? 'current' : 'upcoming'),
            ];
        });
        $logs = ActivityLog::with('user')
            ->when($u->role === 'guru', fn ($q) => $q->whereIn('user_id', $students->pluck('id')))
            ->when($u->role === 'siswa', fn ($q) => $q->where('user_id', $u->id))
            ->latest()->take(6)->get();
        $projects = Project::whereIn('class_id', $classIds)->where('status', 'aktif')->orderBy('deadline')->take(3)->get();
        $classes = $u->role === 'guru'
            ? $u->classes()->with(['academicYear'])->withCount('students')->orderBy('name')->get()
            : ($u->role === 'admin' ? SchoolClass::with(['academicYear'])->withCount('students')->whereIn('id', $classIds)->orderBy('name')->get() : collect());
        $recentProjectSubmissions = $u->role === 'guru'
            ? ProjectSubmission::with(['student', 'project'])->whereHas('project', fn ($q) => $q->whereIn('class_id', $classIds))->latest()->take(5)->get()
            : collect();
        $evaluationQuery = TestResult::whereNotNull('submitted_at')->whereHas('test', fn ($q) => $q->whereIn('class_id', $classIds));
        $evaluationCount = $u->role === 'guru' ? (clone $evaluationQuery)->count() : 0;
        $evaluationAverage = $u->role === 'guru' ? (clone $evaluationQuery)->avg('score') : null;
        $evaluationTestsCount = $u->role === 'guru'
            ? LearningTest::whereIn('class_id', $classIds)->where('status', 'aktif')->count()
            : 0;
        $recentUsers = $u->role === 'admin' ? User::latest()->take(5)->get() : collect();
        $recentContent = $u->role === 'admin' ? Material::with('schoolClass')->latest()->take(5)->get() : collect();
        $activityChart = [];
        if ($u->role === 'admin') {
            $dailyActivity = ActivityLog::selectRaw('DATE(created_at) as day, COUNT(*) as total')
                ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                ->groupBy('day')->orderBy('day')->pluck('total', 'day');
            foreach (range(6, 0) as $daysAgo) {
                $day = now()->subDays($daysAgo)->toDateString();
                $activityChart[] = ['label' => now()->subDays($daysAgo)->locale('id')->translatedFormat('D'), 'count' => (int) ($dailyActivity[$day] ?? 0)];
            }
        }

        return view('dashboard', [
            'year' => $year,
            'progress' => $u->role === 'siswa' ? $p->forStudent($u) : null,
            'students' => $students,
            'classProgress' => round($stats->avg('percent') ?? 0),
            'preDone' => $stats->sum(fn ($s) => $s['parts']['Pre-test']['done']),
            'postDone' => $stats->sum(fn ($s) => $s['parts']['Post-test']['done']),
            'materials' => $materials,
            'currentMaterial' => $currentMaterial,
            'completedMaterialIds' => $completedMaterialIds,
            'dashboardLessons' => $dashboardLessons,
            'projects' => $projects,
            'classes' => $classes,
            'recentProjectSubmissions' => $recentProjectSubmissions,
            'evaluationCount' => $evaluationCount,
            'evaluationTestsCount' => $evaluationTestsCount,
            'evaluationAverage' => $evaluationAverage,
            'recentUsers' => $recentUsers,
            'recentContent' => $recentContent,
            'activityChart' => $activityChart,
            'logs' => $logs,
            'counts' => [
                'Pengguna' => User::count(),
                'Siswa' => User::where('role', 'siswa')->count(),
                'Guru' => User::where('role', 'guru')->count(),
                'Materi aktif' => Material::where('status', 'aktif')->count(),
                'Kelas aktif' => SchoolClass::where('status', 'aktif')->count(),
            ],
        ]);
    }

    public function students(Request $r, Progress $p)
    {
        $q = User::where('role', 'siswa')->whereIn('class_id', $r->user()->classes()->pluck('classes.id'));
        if ($r->filled('q')) {
            $q->where('name', 'like', '%'.$r->q.'%');
        }if ($r->filled('class_id')) {
            $q->where('class_id', $r->class_id);
        }$students = $q->with('schoolClass')->get()->map(fn ($s) => ['user' => $s, 'progress' => $p->forStudent($s)])->filter(fn ($s) => ($r->progress !== 'low' || $s['progress']['percent'] < 50) && ($r->post !== 'pending' || $s['progress']['post'] === null));

        return view('teacher.students', ['students' => $students, 'classes' => $r->user()->classes]);
    }

    public function analytics(Request $r, Progress $p)
    {
        $classes = $r->user()->classes;
        $classIds = $classes->pluck('id');
        if ($r->filled('class_id')) {
            abort_unless($classIds->contains((int) $r->class_id), 403);
            $classIds = collect([(int) $r->class_id]);
        }$students = User::where('role', 'siswa')->whereIn('class_id', $classIds)->get();
        $ids = $students->pluck('id');
        $stats = $students->map(fn ($s) => ['name' => $s->name, 'data' => $p->forStudent($s)]);
        $pre = $stats->avg('data.pre');
        $post = $stats->avg('data.post');
        $paired = $stats->filter(fn ($s) => $s['data']['pre'] !== null && $s['data']['post'] !== null);
        $gain = $paired->avg(fn ($s) => $s['data']['post'] - $s['data']['pre']);
        $materials = Material::whereIn('class_id', $classIds)->where('status', 'aktif')->get()->map(function ($m) use ($students, $ids) {
            $n = $students->where('class_id', $m->class_id)->count();
            $done = MaterialProgress::where('material_id', $m->id)->whereIn('student_id', $ids)->count();

            return ['title' => $m->title, 'percent' => $n ? round(100 * $done / $n) : 0];
        });
        $challenges = Challenge::whereIn('class_id', $classIds)->where('status', 'aktif')->get()->map(function ($c) use ($ids) {
            $s = ChallengeSubmission::where('challenge_id', $c->id)->whereIn('student_id', $ids)->get();

            return ['title' => $c->title, 'attempts' => $s->sum('attempt'), 'success' => $s->where('status', 'selesai')->count(), 'rate' => $s->count() ? round(100 * $s->where('status', 'selesai')->count() / $s->count()) : null];
        })->sortBy('rate');

        return view('teacher.analytics',compact('classes','stats','pre','post','gain','materials','challenges') + ['pairedCount' => $paired->count()]);
    }
}
