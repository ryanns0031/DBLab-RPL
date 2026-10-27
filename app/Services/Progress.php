<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\LearningTest;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\Project;
use App\Models\ProjectStep;
use App\Models\StudentProjectStep;
use App\Models\SqlShopkeeperProgress;
use App\Models\TestResult;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class Progress
{
    public function forStudent(User $u): array
    {
        $materials = Material::where('class_id', $u->class_id)->where('status', 'aktif')->pluck('id');
        $tests = LearningTest::where('class_id', $u->class_id)->where('status', 'aktif')->get();
        $challenges = Challenge::where('class_id', $u->class_id)->where('status', 'aktif')->pluck('id');
        $projects = Project::where('class_id', $u->class_id)->where('status', 'aktif')->pluck('id');
        $steps = ProjectStep::whereIn('project_id', $projects)->pluck('id');
        $counts = ['Materi' => [$materials->count(), MaterialProgress::where('student_id', $u->id)->whereIn('material_id', $materials)->count()],
            'Challenge' => [$challenges->count(), ChallengeSubmission::where('student_id', $u->id)->whereIn('challenge_id', $challenges)->where('status', 'selesai')->count()],
            'Project' => [$steps->count(), StudentProjectStep::where('student_id', $u->id)->whereIn('project_step_id', $steps)->count()]];
        foreach (['pretest' => 'Pre-test', 'posttest' => 'Post-test'] as $type => $label) {
            $ids = $tests->where('type', $type)->pluck('id');
            $counts[$label] = [$ids->count(), TestResult::where('student_id', $u->id)->whereIn('test_id', $ids)->whereNotNull('submitted_at')->count()];
        }
        $total = array_sum(array_column($counts, 0));
        $done = array_sum(array_column($counts, 1));
        $parts = [];
        foreach ($counts as $k => [$n,$d]) {
            $parts[$k] = ['total' => $n, 'done' => $d, 'percent' => $n ? round(100 * $d / $n) : 0];
        }
        $scores = TestResult::where('student_id', $u->id)->whereIn('test_id', $tests->pluck('id'))->whereNotNull('submitted_at')->with('test')->get();
        $topics = Material::whereIn('id', $materials)->orderBy('week')->get()->map(function ($m) use ($u) {
            $total = 1;
            $done = MaterialProgress::where('student_id', $u->id)->where('material_id', $m->id)->exists() ? 1 : 0;
            $ids = Challenge::where('material_id', $m->id)->where('status', 'aktif')->pluck('id');
            $total += $ids->count();
            $done += ChallengeSubmission::where('student_id', $u->id)->whereIn('challenge_id', $ids)->where('status', 'selesai')->count();

            return ['title' => $m->title, 'percent' => round(100 * $done / $total)];
        });

        return ['whereBadge' => ChallengeSubmission::where('student_id', $u->id)->where('status', 'selesai')->whereIn('challenge_id', Challenge::whereIn('id', $challenges)->whereIn('material_id', Material::where('week', 6)->pluck('id'))->pluck('id'))->exists(), 'percent' => $total ? round(100 * $done / $total) : 0, 'parts' => $parts, 'topics' => $topics, 'pre' => $scores->filter(fn ($s) => $s->test->type === 'pretest')->avg('score'), 'post' => $scores->filter(fn ($s) => $s->test->type === 'posttest')->avg('score'), 'xp' => ChallengeSubmission::where('student_id',$u->id)->whereIn('challenge_id',$challenges)->sum('score') + (Schema::hasTable('sql_shopkeeper_progress') ? (int) SqlShopkeeperProgress::where('student_id', $u->id)->value('game_xp') : 0)];
    }
}
