<?php

namespace App\Http\Controllers;

use App\Models\Discussion;
use App\Models\DiscussionCategory;
use App\Models\DiscussionReply;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DiscussionController extends Controller
{
    public function index(Request $request)
    {
        $categories = DiscussionCategory::orderBy('sort_order')->get();
        $selectedCategory = $request->query('category');
        $selectedStatus = $request->query('status'); // belum_terjawab, terjawab
        $sort = $request->query('sort', 'terbaru');
        $search = trim((string) $request->query('q', ''));

        $query = Discussion::with(['user', 'category', 'solvedReply']);

        if ($selectedCategory && $selectedCategory !== 'semua') {
            $query->whereHas('category', function ($q) use ($selectedCategory) {
                $q->where('slug', $selectedCategory);
            });
        }

        if ($selectedStatus && in_array($selectedStatus, ['belum_terjawab', 'terjawab'], true)) {
            $query->where('status', $selectedStatus);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('content', 'like', '%'.$search.'%')
                    ->orWhere('sql_snippet', 'like', '%'.$search.'%');
            });
        }

        match ($sort) {
            'terbanyak_dibalas' => $query->orderBy('replies_count', 'desc')->orderBy('created_at', 'desc'),
            'terlama' => $query->orderBy('created_at', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $discussions = $query->paginate(10)->withQueryString();

        // Summary stats
        $stats = [
            'total' => Discussion::count(),
            'solved' => Discussion::where('status', 'terjawab')->count(),
            'unsolved' => Discussion::where('status', 'belum_terjawab')->count(),
            'total_replies' => DiscussionReply::count(),
        ];

        return view('student.leisure.discussions.index', [
            'discussions' => $discussions,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'selectedStatus' => $selectedStatus,
            'sort' => $sort,
            'search' => $search,
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        $categories = DiscussionCategory::orderBy('sort_order')->get();

        return view('student.leisure.discussions.create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|min:5|max:200',
            'discussion_category_id' => 'required|exists:discussion_categories,id',
            'content' => 'required|string|min:10|max:5000',
            'sql_snippet' => 'nullable|string|max:4000',
        ], [
            'title.required' => 'Judul diskusi wajib diisi.',
            'title.min' => 'Judul diskusi minimal 5 karakter.',
            'title.max' => 'Judul diskusi maksimal 200 karakter.',
            'discussion_category_id.required' => 'Pilih kategori diskusi yang valid.',
            'discussion_category_id.exists' => 'Kategori diskusi yang dipilih tidak tersedia.',
            'content.required' => 'Isi diskusi wajib diisi.',
            'content.min' => 'Isi diskusi minimal 10 karakter agar pertanyaan lebih jelas.',
            'content.max' => 'Isi diskusi maksimal 5.000 karakter.',
            'sql_snippet.max' => 'Potongan kode SQL maksimal 4.000 karakter.',
        ]);

        $baseSlug = Str::slug($validated['title']);
        $slug = $baseSlug ?: 'diskusi-'.Str::random(6);
        $counter = 1;
        while (Discussion::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        $discussion = Discussion::create([
            'user_id' => $request->user()->id,
            'discussion_category_id' => $validated['discussion_category_id'],
            'title' => $validated['title'],
            'slug' => $slug,
            'content' => $validated['content'],
            'sql_snippet' => $validated['sql_snippet'] ?: null,
            'status' => 'belum_terjawab',
            'views_count' => 0,
            'replies_count' => 0,
        ]);

        return redirect()->route('siswa.fun-zone.discussions.show', ['slug' => $discussion->slug])
            ->with('success', 'Topik diskusi berhasil dipublikasikan!');
    }

    public function show(Request $request, string $slug)
    {
        $discussion = Discussion::with(['user', 'category', 'replies.user', 'solvedReply'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment view count safely
        $discussion->increment('views_count');

        return view('student.leisure.discussions.show', [
            'discussion' => $discussion,
        ]);
    }

    public function reply(Request $request, string $slug)
    {
        $discussion = Discussion::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'content' => 'required|string|min:3|max:4000',
            'sql_snippet' => 'nullable|string|max:4000',
        ], [
            'content.required' => 'Balasan tidak boleh kosong.',
            'content.min' => 'Balasan minimal 3 karakter.',
            'content.max' => 'Balasan maksimal 4.000 karakter.',
            'sql_snippet.max' => 'Potongan kode SQL maksimal 4.000 karakter.',
        ]);

        DiscussionReply::create([
            'discussion_id' => $discussion->id,
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
            'sql_snippet' => $validated['sql_snippet'] ?: null,
            'is_solution' => false,
        ]);

        $discussion->increment('replies_count');

        return redirect()->route('siswa.fun-zone.discussions.show', ['slug' => $discussion->slug])
            ->with('success', 'Balasanmu berhasil dikirim!');
    }

    public function markSolution(Request $request, string $slug, int $replyId)
    {
        $user = $request->user();
        $discussion = Discussion::where('slug', $slug)->firstOrFail();
        $reply = DiscussionReply::where('discussion_id', $discussion->id)->findOrFail($replyId);

        // Authorization: Only the topic author or teacher/admin can mark solution
        if ($discussion->user_id !== $user->id && ! in_array($user->role, ['guru', 'admin'], true)) {
            abort(403, 'Hanya penulis topik atau guru yang dapat menandai jawaban.');
        }

        // Toggle solution or set solution
        if ($discussion->solved_reply_id === $reply->id) {
            // Unmark / reopen
            $reply->update(['is_solution' => false]);
            $discussion->update([
                'solved_reply_id' => null,
                'status' => 'belum_terjawab',
            ]);
            $message = 'Tanda jawaban membantu telah dicabut. Status kembali Belum Terjawab.';
        } else {
            // Clear previous solution
            DiscussionReply::where('discussion_id', $discussion->id)->update(['is_solution' => false]);

            $reply->update(['is_solution' => true]);
            $discussion->update([
                'solved_reply_id' => $reply->id,
                'status' => 'terjawab',
            ]);
            $message = 'Jawaban berhasil ditandai sebagai solusi yang membantu!';
        }

        return redirect()->route('siswa.fun-zone.discussions.show', ['slug' => $discussion->slug])
            ->with('success', $message);
    }

    public function destroy(Request $request, string $slug)
    {
        $user = $request->user();
        $discussion = Discussion::where('slug', $slug)->firstOrFail();

        // Authorization: Author or teacher/admin
        if ($discussion->user_id !== $user->id && ! in_array($user->role, ['guru', 'admin'], true)) {
            abort(403, 'Kamu tidak memiliki izin untuk menghapus diskusi ini.');
        }

        $discussion->delete();

        return redirect()->route('siswa.fun-zone.discussions')
            ->with('success', 'Topik diskusi berhasil dihapus.');
    }

    public function destroyReply(Request $request, string $slug, int $replyId)
    {
        $user = $request->user();
        $discussion = Discussion::where('slug', $slug)->firstOrFail();
        $reply = DiscussionReply::where('discussion_id', $discussion->id)->findOrFail($replyId);

        // Authorization: Reply author or teacher/admin
        if ($reply->user_id !== $user->id && ! in_array($user->role, ['guru', 'admin'], true)) {
            abort(403, 'Kamu tidak memiliki izin untuk menghapus balasan ini.');
        }

        // If the deleted reply was the solution, reset discussion status
        if ($discussion->solved_reply_id === $reply->id) {
            $discussion->update([
                'solved_reply_id' => null,
                'status' => 'belum_terjawab',
            ]);
        }

        $reply->delete();
        $discussion->decrement('replies_count');

        return redirect()->route('siswa.fun-zone.discussions.show', ['slug' => $discussion->slug])
            ->with('success', 'Balasan berhasil dihapus.');
    }
}

