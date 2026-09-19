<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleRevision;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GovernanceController extends Controller
{
    public function revisions(Request $request): View
    {
        $articleId = (int) $request->query('article_id', 0);
        $rows = ArticleRevision::query()
            ->with('article')
            ->when($articleId > 0, fn ($q) => $q->where('article_id', $articleId))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.governance.revisions', compact('rows', 'articleId'));
    }

    public function audits(Request $request): View
    {
        $event = trim((string) $request->query('event', ''));
        $rows = AuditLog::query()
            ->when($event !== '', fn ($q) => $q->where('event_type', 'like', '%'.$event.'%'))
            ->latest('id')
            ->paginate(40)
            ->withQueryString();

        return view('admin.governance.audits', compact('rows', 'event'));
    }

    public function destroyRevision(ArticleRevision $revision): RedirectResponse
    {
        $revision->delete();

        return back()->with('success', __('site.admin_governance_revision_deleted'));
    }

    public function clearRevisions(Request $request): RedirectResponse
    {
        $articleId = (int) $request->input('article_id', 0);
        $count = ArticleRevision::query()
            ->when($articleId > 0, fn ($q) => $q->where('article_id', $articleId))
            ->delete();

        return back()->with('success', __('site.admin_governance_revisions_cleared', ['count' => $count]));
    }

    public function destroyAudit(AuditLog $audit): RedirectResponse
    {
        $audit->delete();

        return back()->with('success', __('site.admin_governance_audit_deleted'));
    }

    public function clearAudits(Request $request): RedirectResponse
    {
        $event = trim((string) $request->input('event', ''));
        $count = AuditLog::query()
            ->when($event !== '', fn ($q) => $q->where('event_type', 'like', '%'.$event.'%'))
            ->delete();

        return back()->with('success', __('site.admin_governance_audits_cleared', ['count' => $count]));
    }
}
