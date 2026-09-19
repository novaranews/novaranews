<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommentController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->input('filter', 'pending');

        $query = Comment::with(['article'])
            ->whereNull('parent_id') // Önce üst yorumları göster
            ->orderByDesc('created_at');

        if ($filter === 'pending') {
            $query->where('approved', false);
        } elseif ($filter === 'approved') {
            $query->where('approved', true);
        }
        // 'all' => filtre yok

        $pendingCount = Comment::where('approved', false)->count();

        $comments = $query->paginate(30)->withQueryString();

        return view('admin.comments.index', compact('comments', 'filter', 'pendingCount'));
    }

    public function approve(Comment $comment): RedirectResponse
    {
        $comment->update(['approved' => true]);

        // Yanıtları da otomatik onayla değil — her biri ayrı onaylanır
        return back()->with('status', __('site.admin_comment_approved'));
    }

    public function reject(Comment $comment): RedirectResponse
    {
        $comment->update(['approved' => false]);

        return back()->with('status', __('site.admin_comment_rejected'));
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete(); // cascadeOnDelete ile yanıtları da silinir

        return back()->with('status', __('site.admin_comment_deleted'));
    }
}
