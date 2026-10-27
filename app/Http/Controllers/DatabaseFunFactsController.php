<?php

namespace App\Http\Controllers;

use App\Models\DatabaseFunFact;
use App\Models\DatabaseFunFactRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DatabaseFunFactsController extends Controller
{
    public const CATEGORIES = [
        'Semua',
        'Dunia Nyata',
        'Teknologi',
        'Game & Hiburan',
        'Edukasi',
        'Bisnis',
        'Inovasi',
    ];

    public const TRIVIA_LIST = [
        [
            'text' => 'Dunia menghasilkan lebih dari 2,5 quintillion bytes data setiap hari! Data ini sebagian besar disimpan dan dikelola menggunakan database modern.',
            'source' => 'World Economic Forum & IDC Global DataSphere Report',
        ],
        [
            'text' => 'Model Database Relasional pertama kali dicetuskan oleh Edgar F. Codd di IBM pada tahun 1970 dalam makalah ilmiah yang merevolusi cara dunia menyimpan data.',
            'source' => 'Communications of the ACM, Vol. 13 No. 6 (1970)',
        ],
        [
            'text' => 'Bahasa SQL distandarisasi oleh ANSI pada tahun 1986 dan ISO pada tahun 1987. Hingga kini, SQL tetap menjadi bahasa basis data paling populer di dunia industri.',
            'source' => 'ISO/IEC 9075 Database Language SQL Standard',
        ],
        [
            'text' => 'Struktur indeks B-Tree yang digunakan pada database seperti MySQL dan PostgreSQL mampu menemukan 1 baris dari 100 juta record hanya dalam 4-5 kali pembacaan disk.',
            'source' => 'Database System Concepts (Silberschatz, Korth & Sudarshan)',
        ],
        [
            'text' => 'Mesin ATM dan transaksi kartu bank mengandalkan prinsip ACID (Atomicity, Consistency, Isolation, Durability) agar saldo tidak pernah salah terpotong saat terjadi gangguan listrik.',
            'source' => 'Transaction Processing: Concepts and Techniques (Jim Gray)',
        ],
        [
            'text' => 'Aplikasi peta digital seperti Google Maps dan OpenStreetMap menggunakan Spatial Database dengan indeks R-Tree untuk menghitung rute tercepat dalam hitungan milidetik.',
            'source' => 'ACM SIGMOD Spatial Database Indexing Standards',
        ],
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $selectedCategory = $request->query('category', 'Semua');
        $search = trim((string) $request->query('q', ''));
        $sort = $request->query('sort', 'terbaru');

        if (! in_array($selectedCategory, self::CATEGORIES, true)) {
            $selectedCategory = 'Semua';
        }

        $query = DatabaseFunFact::active()
            ->with(['reads' => function ($q) use ($user) {
                $q->where('student_id', $user->id);
            }]);

        if ($selectedCategory !== 'Semua') {
            $query->where('category', $selectedCategory);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('summary', 'like', '%'.$search.'%')
                    ->orWhere('content', 'like', '%'.$search.'%');
            });
        }

        match ($sort) {
            'terlama' => $query->orderBy('published_at', 'asc')->orderBy('id', 'asc'),
            'az' => $query->orderBy('title', 'asc'),
            default => $query->orderBy('published_at', 'desc')->orderBy('id', 'desc'),
        };

        $facts = $query->get();

        // Exploration progress calculations
        $totalFacts = DatabaseFunFact::active()->count();
        $readCount = DatabaseFunFactRead::where('student_id', $user->id)
            ->whereHas('funFact', fn ($q) => $q->active())
            ->count();
        $progressPercent = $totalFacts > 0 ? (int) round(($readCount / $totalFacts) * 100) : 0;

        // Recommendations (up to 3 items)
        $readFactIds = DatabaseFunFactRead::where('student_id', $user->id)->pluck('database_fun_fact_id')->toArray();
        $unreadRecommendations = DatabaseFunFact::active()
            ->whereNotIn('id', $readFactIds)
            ->latest('published_at')
            ->take(3)
            ->get();

        if ($unreadRecommendations->count() < 3) {
            $needed = 3 - $unreadRecommendations->count();
            $existingIds = $unreadRecommendations->pluck('id')->toArray();
            $fallbackRecommendations = DatabaseFunFact::active()
                ->whereNotIn('id', array_merge($readFactIds, $existingIds))
                ->latest('published_at')
                ->take($needed)
                ->get();

            if ($fallbackRecommendations->count() < $needed) {
                $additionalFallback = DatabaseFunFact::active()
                    ->whereNotIn('id', array_merge($existingIds, $fallbackRecommendations->pluck('id')->toArray()))
                    ->latest('published_at')
                    ->take($needed - $fallbackRecommendations->count())
                    ->get();
                $recommendations = $unreadRecommendations->concat($fallbackRecommendations)->concat($additionalFallback);
            } else {
                $recommendations = $unreadRecommendations->concat($fallbackRecommendations);
            }
        } else {
            $recommendations = $unreadRecommendations;
        }

        return view('student.leisure.database-fun-facts.index', [
            'facts' => $facts,
            'categories' => self::CATEGORIES,
            'selectedCategory' => $selectedCategory,
            'search' => $search,
            'sort' => $sort,
            'totalFacts' => $totalFacts,
            'readCount' => $readCount,
            'progressPercent' => $progressPercent,
            'recommendations' => $recommendations,
            'trivia' => self::TRIVIA_LIST[0],
            'triviaList' => self::TRIVIA_LIST,
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $user = $request->user();
        $fact = DatabaseFunFact::active()->where('slug', $slug)->firstOrFail();

        // Safely record read activity (idempotent, does not duplicate count)
        $readRecord = DatabaseFunFactRead::where('student_id', $user->id)
            ->where('database_fun_fact_id', $fact->id)
            ->first();

        if (! $readRecord) {
            $readRecord = DatabaseFunFactRead::create([
                'student_id' => $user->id,
                'database_fun_fact_id' => $fact->id,
                'first_read_at' => now(),
                'last_read_at' => now(),
            ]);
        } else {
            $readRecord->update(['last_read_at' => now()]);
        }

        // Related facts
        $relatedFacts = DatabaseFunFact::active()
            ->where('id', '!=', $fact->id)
            ->where('category', $fact->category)
            ->take(3)
            ->get();

        if ($relatedFacts->count() < 3) {
            $needed = 3 - $relatedFacts->count();
            $extra = DatabaseFunFact::active()
                ->where('id', '!=', $fact->id)
                ->whereNotIn('id', $relatedFacts->pluck('id'))
                ->latest('published_at')
                ->take($needed)
                ->get();
            $relatedFacts = $relatedFacts->concat($extra);
        }

        return view('student.leisure.database-fun-facts.show', [
            'fact' => $fact,
            'readRecord' => $readRecord,
            'relatedFacts' => $relatedFacts,
        ]);
    }

    public function markAsRead(Request $request, int $id)
    {
        $user = $request->user();
        $fact = DatabaseFunFact::active()->findOrFail($id);

        $readRecord = DatabaseFunFactRead::where('student_id', $user->id)
            ->where('database_fun_fact_id', $fact->id)
            ->first();

        if (! $readRecord) {
            $readRecord = DatabaseFunFactRead::create([
                'student_id' => $user->id,
                'database_fun_fact_id' => $fact->id,
                'first_read_at' => now(),
                'last_read_at' => now(),
            ]);
        } else {
            $readRecord->update(['last_read_at' => now()]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'read_at' => $readRecord->first_read_at->toIso8601String(),
            ]);
        }

        return back()->with('success', 'Fakta berhasil ditandai sebagai sudah dibaca.');
    }

    public function trivia(): JsonResponse
    {
        return response()->json([
            'trivia' => self::TRIVIA_LIST,
        ]);
    }
}

