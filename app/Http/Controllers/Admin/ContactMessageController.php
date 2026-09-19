<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->get('filter', 'all'); // all | unread | read

        $query = ContactMessage::query()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $messages  = $query->paginate(25)->withQueryString();
        $unreadCount = ContactMessage::query()->whereNull('read_at')->count();

        return view('admin.contact-messages.index', compact('messages', 'filter', 'unreadCount'));
    }

    public function show(ContactMessage $contactMessage): View
    {
        $contactMessage->markRead();

        return view('admin.contact-messages.show', ['message' => $contactMessage]);
    }

    public function markRead(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->markRead();

        return back()->with('status', __('site.admin_marked_read'));
    }

    public function markUnread(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->update(['read_at' => null]);

        return back()->with('status', __('site.admin_marked_unread'));
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->delete();

        return redirect()->route('admin.contact-messages.index')->with('status', __('site.admin_message_deleted'));
    }
}
