<?php

namespace App\Http\Controllers;

use App\Models\QueryGuessSession;
use App\Models\User;
use App\Services\QueryGuessGame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueryGuessController extends Controller
{
    public function index(QueryGuessGame $game)
    {
        $session = QueryGuessSession::where('student_id', auth()->id())->latest('id')->first();
        if ($session?->completed_at) {
            return view('student.leisure.query-guess', ['summary' => $session, 'questionCount' => count($session->question_order)]);
        }

        $session ??= $this->newSession($game);
        $questionId = $session->question_order[$session->current_index] ?? null;
        $question = $game->question($questionId);
        abort_unless($question, 500, 'Kumpulan soal game tidak tersedia.');

        $answer = $session->answers[$questionId] ?? null;
        $history = [];
        foreach ($session->question_order as $index => $id) {
            $record = $session->answers[$id] ?? null;
            $history[] = ['number' => $index + 1, 'state' => $record ? ($record['correct'] ? 'correct' : 'wrong') : ($index === $session->current_index ? 'current' : 'pending')];
        }

        $streak = 0;
        foreach (array_reverse(array_slice($session->question_order, 0, $session->current_index + ($answer ? 1 : 0))) as $id) {
            $record = $session->answers[$id] ?? null;
            if (! $record || ! $record['correct']) {
                break;
            }
            $streak++;
        }

        $questionView = $question;
        unset($questionView['answer']);
        $escapedQuery = e($question['query']);
        $questionView['highlighted_query'] = preg_replace_callback(
            "/'[^']*'|\\b(?:SELECT|FROM|WHERE|AND|OR|ORDER BY|GROUP BY|LIMIT|COUNT|SUM|AVG|ROUND|AS|ON|JOIN|DESC|ASC)\\b/i",
            fn ($match) => str_starts_with($match[0], "'")
                ? '<span class="sql-string">'.$match[0].'</span>'
                : '<span class="sql-keyword">'.$match[0].'</span>',
            $escapedQuery,
        );
        $datasetTables = isset($question['columns'][0])
            ? [['name' => 'siswa', 'columns' => $question['columns'], 'rows' => $question['dataset']]]
            : collect($question['dataset'])->map(fn ($rows, $table) => ['name' => $table, 'columns' => $question['columns'][$table], 'rows' => $rows])->values()->all();

        return view('student.leisure.query-guess', [
            'session' => $session,
            'question' => $questionView,
            'questionNumber' => $session->current_index + 1,
            'questionCount' => count($session->question_order),
            'answer' => $answer ? ['choice' => $answer['choice'], 'correct' => $answer['correct'], 'correctOption' => $question['answer']] : null,
            'history' => $history,
            'streak' => $streak,
            'datasetTables' => $datasetTables,
        ]);
    }

    public function answer(Request $request, QueryGuessGame $game)
    {
        $data = $request->validate([
            'question_id' => 'required|string|max:80',
            'choice' => 'required|in:A,B,C,D',
        ]);

        $result = DB::transaction(function () use ($data, $game) {
            $session = QueryGuessSession::where('student_id', auth()->id())->whereNull('completed_at')->latest('id')->lockForUpdate()->firstOrFail();
            $currentId = $session->question_order[$session->current_index] ?? null;
            abort_unless($currentId === $data['question_id'], 409, 'Pertanyaan ini bukan pertanyaan yang sedang aktif.');

            $answers = $session->answers ?? [];
            if (isset($answers[$currentId])) {
                return ['duplicate' => true];
            }

            $question = $game->question($currentId);
            abort_unless($question && isset($question['options'][$data['choice']]), 422, 'Pilihan jawaban tidak valid.');
            $correct = $data['choice'] === $question['answer'];
            $answers[$currentId] = ['choice' => $data['choice'], 'correct' => $correct, 'answered_at' => now()->toIso8601String()];
            $session->answers = $answers;
            $session->score += $correct ? QueryGuessGame::POINTS_PER_ANSWER : 0;
            $session->correct_count += $correct ? 1 : 0;
            $session->wrong_count += $correct ? 0 : 1;
            if ($session->current_index === count($session->question_order) - 1) {
                $session->completed_at = now();
            }
            $session->save();

            return ['duplicate' => false, 'completed' => (bool) $session->completed_at];
        });

        if ($result['duplicate']) {
            return back()->with('feedback', 'Jawaban untuk pertanyaan ini sudah dikunci. Lanjutkan ke soal berikutnya.');
        }

        return redirect()->route('siswa.fun-zone.query-guess')->with('answer_saved', true);
    }

    public function next()
    {
        DB::transaction(function () {
            $session = QueryGuessSession::where('student_id', auth()->id())->whereNull('completed_at')->latest('id')->lockForUpdate()->firstOrFail();
            $questionId = $session->question_order[$session->current_index] ?? null;
            abort_unless($questionId && isset(($session->answers ?? [])[$questionId]), 409, 'Jawab pertanyaan ini sebelum melanjutkan.');
            abort_if($session->current_index >= count($session->question_order) - 1, 409, 'Permainan sudah selesai.');
            $session->current_index++;
            $session->save();
        });

        return redirect()->route('siswa.fun-zone.query-guess');
    }

    public function reset(QueryGuessGame $game)
    {
        DB::transaction(function () {
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            QueryGuessSession::where('student_id', auth()->id())->whereNull('completed_at')->latest('id')->lockForUpdate()->first()?->delete();
        });
        $this->newSession($game);

        return redirect()->route('siswa.fun-zone.query-guess');
    }

    public function startNew(QueryGuessGame $game)
    {
        $this->newSession($game);

        return redirect()->route('siswa.fun-zone.query-guess');
    }

    private function newSession(QueryGuessGame $game): QueryGuessSession
    {
        $ids = array_keys($game->questions());

        return DB::transaction(function () use ($ids) {
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            $active = QueryGuessSession::where('student_id', auth()->id())->whereNull('completed_at')->latest('id')->first();
            if ($active) {
                return $active;
            }

            return QueryGuessSession::create([
                'student_id' => auth()->id(),
                'question_order' => $ids,
                'answers' => [],
                'score' => 0,
                'correct_count' => 0,
                'wrong_count' => 0,
                'current_index' => 0,
            ]);
        });
    }
}
