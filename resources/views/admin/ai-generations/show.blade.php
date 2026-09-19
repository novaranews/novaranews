<x-app-layout>
    <x-slot name="header">
        <div class="admin-toolbar">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ __('site.admin_ai_generation_detail', ['id' => $aiGeneration->id]) }}</h2>
            <x-admin.btn :href="route('admin.ai-generations.index')">← {{ __('site.admin_contact_back') }}</x-admin.btn>
        </div>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-5xl">

            @if (session('success'))
                <div class="rounded border border-green-200 bg-green-50 px-4 py-2 text-green-800">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded border border-red-200 bg-red-50 px-4 py-2 text-red-800">{{ $errors->first() }}</div>
            @endif

            {{-- Status & Meta --}}
            <div class="admin-card p-4 sm:p-6">
                <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                    <div><dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('site.admin_status_label') }}</dt>
                        <dd class="mt-1">
                            @php $colors = ['pending'=>'text-yellow-700','processing'=>'text-blue-700','done'=>'text-green-700','failed'=>'text-red-700']; @endphp
                            <span class="font-semibold {{ $colors[$aiGeneration->status] ?? '' }}">{{ $aiGeneration->status }}</span>
                        </dd>
                    </div>
                    <div><dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('site.admin_source_language') }}</dt><dd class="mt-1 uppercase">{{ $aiGeneration->source_locale }}</dd></div>
                    <div><dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('site.admin_model') }}</dt><dd class="mt-1">{{ $aiGeneration->model ?? '-' }}</dd></div>
                    <div><dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('site.admin_input_tokens') }}</dt><dd class="mt-1">{{ number_format((int) $aiGeneration->input_tokens) }}</dd></div>
                    <div><dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('site.admin_output_tokens') }}</dt><dd class="mt-1">{{ number_format((int) $aiGeneration->output_tokens) }}</dd></div>
                    <div><dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('site.admin_cost') }}</dt><dd class="mt-1">${{ $aiGeneration->estimated_cost_usd ? number_format((float) $aiGeneration->estimated_cost_usd, 4) : '-' }}</dd></div>
                    <div><dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('site.admin_rss_source') }}</dt><dd class="mt-1">{{ $aiGeneration->newsSource?->name ?? '-' }}</dd></div>
                    <div><dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('site.admin_created_at') }}</dt><dd class="mt-1">{{ $aiGeneration->created_at->format('d.m.Y H:i') }}</dd></div>
                    @if($aiGeneration->approved_at)
                        <div><dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('site.admin_approved_by') }}</dt><dd class="mt-1">{{ $aiGeneration->approvedBy?->name ?? '-' }}</dd></div>
                    @endif
                </dl>
            </div>

            {{-- Source --}}
            <div class="admin-card p-4 sm:p-6">
                <h3 class="mb-3 font-semibold text-gray-800 dark:text-gray-100">{{ __('site.admin_source_news') }}</h3>
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $aiGeneration->source_title }}</p>
                @if($aiGeneration->source_url)
                    <a href="{{ $aiGeneration->source_url }}" target="_blank" rel="noopener" class="mt-1 block truncate text-xs text-blue-600 hover:underline dark:text-blue-300">{{ $aiGeneration->source_url }}</a>
                @endif
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">{{ $aiGeneration->source_text }}</p>
            </div>

            {{-- Error --}}
            @if($aiGeneration->error_message)
                <div class="rounded-lg border border-red-200 bg-red-50 p-6 dark:border-red-800 dark:bg-red-500/10">
                    <h3 class="mb-2 font-semibold text-red-800 dark:text-red-300">{{ __('site.admin_error') }}</h3>
                    <pre class="whitespace-pre-wrap text-xs text-red-700 dark:text-red-300">{{ $aiGeneration->error_message }}</pre>
                    <form action="{{ route('admin.ai-generations.retry', $aiGeneration) }}" method="post" class="mt-3">
                        @csrf
                        <x-admin.btn type="submit" variant="warning">{{ __('site.admin_retry') }}</x-admin.btn>
                    </form>
                </div>
            @endif

            {{-- Generated Article --}}
            @if($aiGeneration->article)
                @php $tr = $aiGeneration->article->translations->first(); @endphp
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 shadow-sm dark:border-green-800 dark:bg-green-500/10 sm:p-6">
                    <h3 class="mb-3 font-semibold text-green-800 dark:text-green-300">{{ __('site.admin_generated_article') }}</h3>
                    @if($tr)
                        <p class="text-base font-bold text-gray-900 dark:text-gray-100">{{ $tr->title }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_url_slug_label') }}: {{ $tr->slug }}</p>
                        @if($tr->excerpt)
                            <p class="mt-2 text-sm italic text-gray-700 dark:text-gray-300">{{ $tr->excerpt }}</p>
                        @endif
                        @if($aiGeneration->image_url)
                            <div class="mt-3">
                                <img src="{{ $aiGeneration->image_url }}" alt="{{ $aiGeneration->image_alt }}" class="h-40 max-w-full rounded bg-stone-100 object-contain object-left dark:bg-stone-800">
                            </div>
                        @endif
                        <div class="mt-3 flex gap-2">
                            <x-admin.btn :href="route('admin.articles.edit', $aiGeneration->article)" variant="info" class="hover:bg-indigo-500">{{ __('site.admin_edit_article') }}</x-admin.btn>
                        </div>
                    @endif

                    @if(! $aiGeneration->approved_at)
                        <form action="{{ route('admin.ai-generations.approve', $aiGeneration) }}" method="post" class="mt-4 border-t border-green-200 pt-4 dark:border-green-800">
                            @csrf
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('site.admin_approval_note_optional') }}</label>
                            <textarea name="note" rows="2" class="w-full rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" placeholder="{{ __('site.admin_approval_note_placeholder') }}"></textarea>
                            <x-admin.btn type="submit" variant="success" class="mt-2">{{ __('site.admin_approve_publish') }}</x-admin.btn>
                        </form>
                    @else
                        <div class="mt-4 rounded border border-green-300 bg-green-100 px-4 py-2 text-sm text-green-800 dark:border-green-700 dark:bg-green-500/20 dark:text-green-300">
                            {{ __('site.admin_approved_at') }}: {{ $aiGeneration->approved_at->format('d.m.Y H:i') }}
                            @if($aiGeneration->approval_note) - {{ $aiGeneration->approval_note }} @endif
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
