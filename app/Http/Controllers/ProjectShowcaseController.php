<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectShowcase;
use App\Models\ProjectShowcaseFavorite;
use App\Models\ProjectShowcaseLike;
use App\Models\ProjectSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectShowcaseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $categories = ProjectShowcase::CATEGORIES;
        $selectedCategory = $request->query('category');
        $sort = $request->query('sort', 'terbaru');
        $search = trim((string) $request->query('q', ''));
        $filterFav = $request->boolean('favorit');
        $filterMine = $request->boolean('saya');

        $query = ProjectShowcase::with(['student', 'likes', 'favorites']);

        // Only show approved showcase for general audience, unless viewing 'saya'
        if ($filterMine && $user) {
            $query->where('student_id', $user->id);
        } else {
            $query->approved();
        }

        if ($filterFav && $user) {
            $query->whereHas('favorites', fn ($q) => $q->where('user_id', $user->id));
        }

        if ($selectedCategory && in_array($selectedCategory, $categories, true)) {
            $query->where('category', $selectedCategory);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('short_description', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        match ($sort) {
            'terpopuler' => $query->orderBy('likes_count', 'desc')->orderBy('created_at', 'desc'),
            'terlama' => $query->orderBy('created_at', 'asc'),
            default => $query->orderBy('published_at', 'desc')->orderBy('created_at', 'desc'),
        };

        $showcases = $query->paginate(9)->withQueryString();

        // Statistics
        $stats = [
            'total_approved' => ProjectShowcase::approved()->count(),
            'categories_count' => ProjectShowcase::approved()->distinct('category')->count('category'),
            'total_likes' => ProjectShowcase::approved()->sum('likes_count'),
            'my_approved_count' => $user ? ProjectShowcase::where('student_id', $user->id)->approved()->count() : 0,
            'my_pending_count' => $user ? ProjectShowcase::where('student_id', $user->id)->where('status', ProjectShowcase::STATUS_PENDING)->count() : 0,
        ];

        return view('student.leisure.showcase.index', [
            'showcases' => $showcases,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'sort' => $sort,
            'search' => $search,
            'filterFav' => $filterFav,
            'filterMine' => $filterMine,
            'stats' => $stats,
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        // Check if student has finished any projects from submissions
        $completedSubmissions = ProjectSubmission::where('student_id', $user->id)
            ->with('project')
            ->get();

        return view('student.leisure.showcase.create', [
            'categories' => ProjectShowcase::CATEGORIES,
            'completedSubmissions' => $completedSubmissions,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|min:4|max:150',
            'category' => 'required|string|in:'.implode(',', ProjectShowcase::CATEGORIES),
            'short_description' => 'required|string|min:10|max:255',
            'description' => 'required|string|min:20|max:5000',
            'purpose' => 'nullable|string|max:2000',
            'problem_solved' => 'nullable|string|max:2000',
            'features' => 'nullable|string|max:3000',
            'project_id' => 'nullable|exists:projects,id',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'erd_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'sql_examples' => 'nullable|string|max:4000',
            'challenges_and_solutions' => 'nullable|string|max:3000',
            'lessons_learned' => 'nullable|string|max:3000',
            'future_improvements' => 'nullable|string|max:3000',
        ], [
            'title.required' => 'Judul project wajib diisi.',
            'category.required' => 'Pilih kategori project yang sesuai.',
            'category.in' => 'Kategori yang dipilih tidak valid.',
            'short_description.required' => 'Deskripsi singkat wajib diisi.',
            'description.required' => 'Deskripsi lengkap wajib diisi.',
            'thumbnail.image' => 'File thumbnail harus berupa gambar.',
            'thumbnail.max' => 'Ukuran thumbnail maksimal 2MB.',
            'erd_image.image' => 'File diagram ERD harus berupa gambar.',
            'erd_image.max' => 'Ukuran diagram ERD maksimal 3MB.',
        ]);

        if (! empty($validated['project_id'])) {
            abort_unless(ProjectSubmission::where('student_id', $user->id)
                ->where('project_id', $validated['project_id'])
                ->exists(), 403);
        }

        $slugBase = Str::slug($validated['title']);
        $slug = $slugBase ?: 'project-'.Str::random(6);
        $counter = 1;
        while (ProjectShowcase::where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$counter;
            $counter++;
        }

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('showcase/thumbnails', 'public');
        }

        $erdPath = null;
        if ($request->hasFile('erd_image')) {
            $erdPath = $request->file('erd_image')->store('showcase/erds', 'public');
        }

        $showcase = ProjectShowcase::create([
            'student_id' => $user->id,
            'project_id' => $validated['project_id'] ?? null,
            'title' => $validated['title'],
            'slug' => $slug,
            'category' => $validated['category'],
            'short_description' => $validated['short_description'],
            'description' => $validated['description'],
            'purpose' => $validated['purpose'] ?? null,
            'problem_solved' => $validated['problem_solved'] ?? null,
            'features' => $validated['features'] ?? null,
            'thumbnail_path' => $thumbnailPath,
            'erd_path' => $erdPath,
            'sql_examples' => $validated['sql_examples'] ?? null,
            'challenges_and_solutions' => $validated['challenges_and_solutions'] ?? null,
            'lessons_learned' => $validated['lessons_learned'] ?? null,
            'future_improvements' => $validated['future_improvements'] ?? null,
            'status' => ProjectShowcase::STATUS_PENDING,
            'likes_count' => 0,
            'views_count' => 0,
        ]);

        return redirect()->route('siswa.fun-zone.showcase')
            ->with('success', 'Karya berhasil diajukan! Guru pembimbing akan meninjau project-mu sebelum dipublikasikan di galeri.');
    }

    public function show(Request $request, string $slug)
    {
        $user = $request->user();
        $showcase = ProjectShowcase::with(['student', 'likes', 'favorites'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Authorization check: unapproved showcase can only be viewed by author or teacher/admin
        $isAuthor = $user && $user->id === $showcase->student_id;
        $isStaff = $user && in_array($user->role, ['guru', 'admin'], true);

        if (! $showcase->isApproved() && ! $isAuthor && ! $isStaff) {
            abort(404);
        }

        $showcase->increment('views_count');

        // Other related works in same category
        $related = ProjectShowcase::approved()
            ->where('id', '!=', $showcase->id)
            ->where('category', $showcase->category)
            ->take(3)
            ->get();

        return view('student.leisure.showcase.show', [
            'showcase' => $showcase,
            'related' => $related,
            'isAuthor' => $isAuthor,
            'isStaff' => $isStaff,
        ]);
    }

    public function like(Request $request, string $slug)
    {
        $user = $request->user();
        $showcase = ProjectShowcase::approved()->where('slug', $slug)->firstOrFail();

        $like = ProjectShowcaseLike::where('user_id', $user->id)
            ->where('project_showcase_id', $showcase->id)
            ->first();

        if ($like) {
            // Unlike
            $like->delete();
            $showcase->decrement('likes_count');
            $liked = false;
        } else {
            // Like
            ProjectShowcaseLike::create([
                'user_id' => $user->id,
                'project_showcase_id' => $showcase->id,
            ]);
            $showcase->increment('likes_count');
            $liked = true;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'liked' => $liked,
                'likes_count' => $showcase->fresh()->likes_count,
            ]);
        }

        return back()->with('success', $liked ? 'Terima kasih atas apresiasimu!' : 'Apresiasi dibatalkan.');
    }

    public function favorite(Request $request, string $slug)
    {
        $user = $request->user();
        $showcase = ProjectShowcase::approved()->where('slug', $slug)->firstOrFail();

        $fav = ProjectShowcaseFavorite::where('user_id', $user->id)
            ->where('project_showcase_id', $showcase->id)
            ->first();

        if ($fav) {
            $fav->delete();
            $favorited = false;
        } else {
            ProjectShowcaseFavorite::create([
                'user_id' => $user->id,
                'project_showcase_id' => $showcase->id,
            ]);
            $favorited = true;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'favorited' => $favorited,
            ]);
        }

        return back()->with('success', $favorited ? 'Karya ditambahkan ke favorit pribadimu.' : 'Karya dihapus dari favorit.');
    }

    // Teacher & Admin Management
    public function reviewIndex(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['guru', 'admin'], true), 403);

        $status = $request->query('status');
        $query = ProjectShowcase::with(['student', 'reviewer'])->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $submissions = $query->paginate(15)->withQueryString();

        return view('teacher.showcase-review', [
            'submissions' => $submissions,
            'selectedStatus' => $status,
        ]);
    }

    public function reviewUpdate(Request $request, int $id)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['guru', 'admin'], true), 403);

        $showcase = ProjectShowcase::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', [
                ProjectShowcase::STATUS_APPROVED,
                ProjectShowcase::STATUS_REVISION,
                ProjectShowcase::STATUS_REJECTED,
            ]),
            'review_notes' => 'nullable|string|max:1000',
        ]);

        $showcase->status = $validated['status'];
        $showcase->review_notes = $validated['review_notes'] ?? null;
        $showcase->reviewed_by = $user->id;
        $showcase->reviewed_at = now();

        if ($validated['status'] === ProjectShowcase::STATUS_APPROVED && ! $showcase->published_at) {
            $showcase->published_at = now();
        }

        $showcase->save();

        return back()->with('success', 'Status pengajuan karya berhasil diperbarui menjadi: '.str_replace('_', ' ', $showcase->status));
    }
}

