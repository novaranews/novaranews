<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use App\Support\RecaptchaVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, string $locale, Article $article, RecaptchaVerifier $recaptcha): RedirectResponse
    {
        // Sadece yayındaki haberlere yorum yapılabilir
        if ($article->status !== 'published') {
            abort(404);
        }

        if (! $recaptcha->verify($request, 'comment')) {
            return back()->withErrors(['captcha' => __('site.contact_captcha_failed')])->withInput();
        }

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'email'     => ['required', 'email', 'max:255'],
            'body'      => ['required', 'string', 'min:3', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        // parent_id varsa sadece aynı habere ait ve onaylanmış yoruma yanıt verilebilir
        if (! empty($validated['parent_id'])) {
            $parent = Comment::find($validated['parent_id']);
            if (! $parent || $parent->article_id !== $article->id || ! $parent->approved || $parent->parent_id !== null) {
                $validated['parent_id'] = null;
            }
        }

        Comment::create([
            'article_id' => $article->id,
            'parent_id'  => $validated['parent_id'] ?? null,
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'body'       => $validated['body'],
            'ip'         => $request->ip(),
            'approved'   => false,
        ]);

        $url = $article->publicUrl() ?? url()->previous();

        return redirect()->to($url)->with('comment_status', 'pending');
    }
}
