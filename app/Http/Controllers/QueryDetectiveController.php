<?php

namespace App\Http\Controllers;

use App\Models\QueryDetectiveSession;
use App\Models\User;
use App\Services\QueryDetectiveGame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueryDetectiveController extends Controller
{
    public function index(QueryDetectiveGame $game)
    {
        $session = QueryDetectiveSession::where('student_id', auth()->id())->latest('id')->first();
        if ($session?->completed_at) {
            return view('student.leisure.query-detective', ['summary' => $session, 'caseCount' => count($session->case_order)]);
        }

        $session ??= $this->newSession($game);
        $caseId = $session->case_order[$session->current_index] ?? null;
        $case = $game->case($caseId);
        abort_unless($case, 500, 'Kumpulan kasus Query Detective tidak tersedia.');
        $progress = $session->case_progress ?? [];
        $caseState = $progress[$caseId] ?? ['attempts' => 0, 'selected' => [], 'solved' => false];

        $history = [];
        foreach ($session->case_order as $index => $id) {
            $state = $progress[$id] ?? null;
            $history[] = ['number' => $index + 1, 'state' => ($state['solved'] ?? false) ? 'solved' : ($index === $session->current_index ? 'current' : 'pending')];
        }

        $safeCase = $case;
        unset($safeCase['wrongTokenIds'], $safeCase['correctedQuery'], $safeCase['results'], $safeCase['explanation'], $safeCase['corrections']);
        foreach ($safeCase['tokens'] as &$token) {
            unset($token['wrong']);
        }
        unset($token);
        foreach ($safeCase['lines'] as &$line) {
            foreach ($line as &$token) {
                unset($token['wrong']);
            }
            unset($token);
        }
        unset($line);

        $solution = null;
        if ($caseState['solved'] ?? false) {
            $wrongTokens = array_column(array_filter($case['tokens'], fn ($token) => $token['wrong']), 'text');
            $solution = [
                'correctedQuery' => $case['correctedQuery'],
                'results' => $case['results'],
                'explanation' => $case['explanation'],
                'corrections' => $case['corrections'],
                'wrongTokens' => $wrongTokens,
                'resultSummary' => 'Query yang benar akan menampilkan hasil sesuai permintaan: '.$case['description'],
            ];
        }

        return view('student.leisure.query-detective', [
            'session' => $session,
            'case' => $safeCase,
            'caseId' => $caseId,
            'caseNumber' => $session->current_index + 1,
            'caseCount' => count($session->case_order),
            'caseState' => $caseState,
            'history' => $history,
            'solution' => $solution,
            'progressPercent' => (int) round(100 * ($session->solved_count / count($session->case_order))),
        ]);
    }

    public function check(Request $request, QueryDetectiveGame $game)
    {
        $data = $request->validate([
            'case_id' => 'required|string|max:80',
            'tokens' => 'required|array|min:1|max:40',
            'tokens.*' => 'required|string|distinct|max:80',
        ]);

        $result = DB::transaction(function () use ($data, $game) {
            $session = QueryDetectiveSession::where('student_id', auth()->id())->whereNull('completed_at')->latest('id')->lockForUpdate()->firstOrFail();
            $caseId = $session->case_order[$session->current_index] ?? null;
            abort_unless($caseId === $data['case_id'], 409, 'Kasus ini bukan kasus yang sedang aktif.');

            $case = $game->case($caseId);
            abort_unless($case, 422, 'Kasus tidak valid.');
            $allowedIds = array_column($case['tokens'], 'id');
            abort_if(array_diff($data['tokens'], $allowedIds), 422, 'Pilihan token tidak valid.');

            $caseProgress = $session->case_progress ?? [];
            $state = $caseProgress[$caseId] ?? ['attempts' => 0, 'selected' => [], 'solved' => false];
            if ($state['solved']) {
                return ['duplicate' => true];
            }

            $selected = array_values(array_unique($data['tokens']));
            $expected = $case['wrongTokenIds'];
            sort($selected);
            sort($expected);
            $correct = $selected === $expected;
            $state['attempts']++;
            $state['selected'] = $data['tokens'];
            $state['solved'] = $correct;
            $state['answered_at'] = now()->toIso8601String();
            $caseProgress[$caseId] = $state;
            $session->case_progress = $caseProgress;

            if ($correct) {
                $session->score += QueryDetectiveGame::POINTS_PER_CASE;
                $session->solved_count++;
                if ($session->current_index === count($session->case_order) - 1) {
                    $session->completed_at = now();
                }
            }
            $session->save();

            return ['duplicate' => false, 'correct' => $correct];
        });

        if ($result['duplicate']) {
            return back()->with('feedback', 'Kasus ini sudah selesai dan tidak dapat dinilai ulang.');
        }

        return redirect()->route('siswa.fun-zone.query-detective')->with($result['correct'] ? 'success' : 'feedback', $result['correct']
            ? 'Bagus! Kamu berhasil menemukan kesalahan pada query.'
            : 'Belum tepat. Periksa kembali keyword, operator, dan kondisi query lalu coba lagi.');
    }

    public function next()
    {
        DB::transaction(function () {
            $session = QueryDetectiveSession::where('student_id', auth()->id())->whereNull('completed_at')->latest('id')->lockForUpdate()->firstOrFail();
            $caseId = $session->case_order[$session->current_index] ?? null;
            abort_unless($caseId && (($session->case_progress[$caseId]['solved'] ?? false)), 409, 'Selesaikan kasus ini sebelum melanjutkan.');
            abort_if($session->current_index >= count($session->case_order) - 1, 409, 'Permainan sudah selesai.');
            $session->current_index++;
            $session->save();
        });

        return redirect()->route('siswa.fun-zone.query-detective');
    }

    public function previous()
    {
        DB::transaction(function () {
            $session = QueryDetectiveSession::where('student_id', auth()->id())->whereNull('completed_at')->latest('id')->lockForUpdate()->firstOrFail();
            abort_if($session->current_index < 1, 409, 'Ini adalah kasus pertama.');
            $session->current_index--;
            $session->save();
        });

        return redirect()->route('siswa.fun-zone.query-detective');
    }

    public function reset(QueryDetectiveGame $game)
    {
        DB::transaction(function () {
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            QueryDetectiveSession::where('student_id', auth()->id())->whereNull('completed_at')->latest('id')->lockForUpdate()->first()?->delete();
        });
        $this->newSession($game);

        return redirect()->route('siswa.fun-zone.query-detective');
    }

    public function startNew(QueryDetectiveGame $game)
    {
        $this->newSession($game);

        return redirect()->route('siswa.fun-zone.query-detective');
    }

    private function newSession(QueryDetectiveGame $game): QueryDetectiveSession
    {
        $caseIds = array_keys($game->cases());

        return DB::transaction(function () use ($caseIds) {
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            $active = QueryDetectiveSession::where('student_id', auth()->id())->whereNull('completed_at')->latest('id')->first();
            if ($active) {
                return $active;
            }

            return QueryDetectiveSession::create([
                'student_id' => auth()->id(),
                'case_order' => $caseIds,
                'case_progress' => [],
                'score' => 0,
                'solved_count' => 0,
                'current_index' => 0,
            ]);
        });
    }
}
