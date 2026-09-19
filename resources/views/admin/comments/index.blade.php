<x-app-layout>
    <x-slot name="header">
        <div class="admin-toolbar">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                {{ __('site.admin_comments_title') }}
                @if($pendingCount > 0)
                    <span class="ml-2 inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-500/20 dark:text-red-300">{{ $pendingCount }}</span>
                @endif
            </h2>
        </div>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-6xl">

            @if(session('status'))
                <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700 dark:bg-green-900/20 dark:text-green-300">{{ session('status') }}</div>
            @endif

            {{-- Filtreler --}}
            <div class="flex flex-wrap gap-2 text-sm">
                @foreach(['pending' => __('site.admin_comment_filter_pending'), 'approved' => __('site.admin_comment_filter_approved'), 'all' => __('site.admin_filter_all')] as $key => $label)
                    <a href="{{ route('admin.comments.index', ['filter' => $key]) }}"
                       class="rounded px-3 py-1.5 font-medium {{ $filter === $key ? 'bg-gray-800 text-white dark:bg-gray-700' : 'border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            @if($comments->isEmpty())
                <div class="rounded border border-gray-200 bg-white p-8 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                    {{ __('site.admin_comments_empty') }}
                </div>
            @else
                <div class="space-y-3">
                    @foreach($comments as $comment)
                        <div class="rounded-lg border {{ $comment->approved ? 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' : 'border-amber-200 bg-amber-50 dark:border-amber-700/50 dark:bg-amber-950/20' }} p-4 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $comment->name }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $comment->email }}</span>
                                        @if(!$comment->approved)
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-800/30 dark:text-amber-300">{{ __('site.admin_comment_pending') }}</span>
                                        @else
                                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-800/30 dark:text-green-300">{{ __('site.admin_comment_approved_badge') }}</span>
                                        @endif
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ $comment->created_at->format('d M Y H:i') }}</span>
                                    </div>

                                    {{-- Hangi makale --}}
                                    @if($comment->article)
                                        <div class="mt-1">
                                            <a href="{{ $comment->article->publicUrl() ?? '#' }}" target="_blank" rel="noopener"
                                               class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                                                {{ $comment->article->translate()?->title ?? 'Makale #'.$comment->article_id }}
                                            </a>
                                        </div>
                                    @endif

                                    {{-- Yorum metni --}}
                                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $comment->body }}</p>

                                    {{-- Yanıtlar --}}
                                    @php $replies = $comment->allReplies()->with('article')->get(); @endphp
                                    @if($replies->isNotEmpty())
                                        <div class="mt-3 space-y-2 border-l-2 border-gray-200 pl-3 dark:border-gray-600">
                                            @foreach($replies as $reply)
                                                <div class="rounded bg-gray-50 p-2 text-sm dark:bg-gray-900/40">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $reply->name }}</span>
                                                        @if(!$reply->approved)
                                                            <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-xs text-amber-800 dark:bg-amber-800/30 dark:text-amber-300">{{ __('site.admin_comment_pending') }}</span>
                                                        @endif
                                                        <span class="text-xs text-gray-400">{{ $reply->created_at->format('d M Y H:i') }}</span>
                                                    </div>
                                                    <p class="mt-1 text-gray-600 dark:text-gray-400">{{ $reply->body }}</p>
                                                    <div class="mt-1.5 flex gap-2">
                                                        @if(!$reply->approved)
                                                            <form method="POST" action="{{ route('admin.comments.approve', $reply) }}">
                                                                @csrf @method('PATCH')
                                                                <button type="submit" class="text-xs text-green-700 hover:underline dark:text-green-400">{{ __('site.admin_comment_approve') }}</button>
                                                            </form>
                                                        @endif
                                                        <form method="POST" action="{{ route('admin.comments.destroy', $reply) }}" onsubmit="return confirm('{{ __('site.admin_comment_delete_confirm') }}')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="text-xs text-red-600 hover:underline dark:text-red-400">{{ __('site.common_delete') }}</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                {{-- Aksiyonlar --}}
                                <div class="flex shrink-0 flex-wrap gap-2">
                                    @if(!$comment->approved)
                                        <form method="POST" action="{{ route('admin.comments.approve', $comment) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                class="rounded border border-green-300 bg-green-50 px-3 py-1 text-xs font-medium text-green-800 hover:bg-green-100 dark:border-green-700 dark:bg-green-900/20 dark:text-green-300 dark:hover:bg-green-900/40">
                                                ✓ {{ __('site.admin_comment_approve') }}
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.comments.reject', $comment) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                class="rounded border border-gray-300 px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                                {{ __('site.admin_comment_unpublish') }}
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.comments.destroy', $comment) }}"
                                          onsubmit="return confirm('{{ __('site.admin_comment_delete_confirm') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="rounded border border-red-300 bg-red-50 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-100 dark:border-red-700 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40">
                                            {{ __('site.common_delete') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">{{ $comments->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
