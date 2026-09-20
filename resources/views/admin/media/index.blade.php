<x-app-layout>
    <x-slot name="header">
        <div class="admin-toolbar">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ __('site.admin_media_library') }}</h2>
            <form action="{{ route('admin.media.optimize-all') }}" method="post"
                  onsubmit="return confirm('All PNG/JPG files will be converted to WebP. Continue?')">
                @csrf
                <x-admin.btn type="submit" variant="info">Optimize all (→ WebP)</x-admin.btn>
            </form>
        </div>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-7xl">
            @if (session('success'))
                <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-2 text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-2 text-red-800">{{ session('error') }}</div>
            @endif

            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">{{ __('site.admin_media_storage_hint') }}</p>
                <form action="{{ route('admin.media.store') }}" method="post" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <x-input-label for="file" :value="__('site.admin_media_image_file')" />
                        <input id="file" type="file" name="file" accept="image/*" required class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300" />
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>
                    <x-admin.btn type="submit" variant="primary">{{ __('site.admin_media_upload') }}</x-admin.btn>
                </form>
            </div>

            <div class="admin-table-wrap">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ __('site.admin_media_preview') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ __('site.admin_media_path') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ __('site.admin_media_size') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ __('site.admin_media_modified') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($files as $f)
                            @php
                                $ext = strtolower(pathinfo($f['path'], PATHINFO_EXTENSION));
                                $sizeKb = $f['size'] / 1024;
                                $isLarge = $sizeKb > 500;
                            @endphp
                            <tr class="{{ $isLarge ? 'bg-amber-50 dark:bg-amber-900/10' : '' }}">
                                <td class="px-4 py-3">
                                    <button type="button" class="js-lightbox-open block" data-url="{{ $f['url'] }}">
                                        <img src="{{ $f['url'] }}" alt=""
                                             class="h-12 w-16 rounded bg-stone-100 object-contain object-center transition hover:opacity-75 dark:bg-stone-800"
                                             loading="lazy" />
                                    </button>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-800 dark:text-gray-200 break-all">
                                    {{ $f['path'] }}
                                    <span class="ml-1 inline-block rounded px-1 py-0.5 text-[10px] font-semibold uppercase
                                        {{ $ext === 'webp' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' }}">
                                        {{ $ext }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm {{ $isLarge ? 'font-semibold text-amber-700 dark:text-amber-400' : 'text-gray-600 dark:text-gray-400' }}">
                                    {{ number_format($sizeKb, 1) }} KB
                                    @if($isLarge)
                                        <span class="ml-1 text-xs">⚠</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Carbon::createFromTimestamp($f['modified'])->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    <button
                                        type="button"
                                        class="js-copy-url mr-3 text-indigo-600 hover:text-indigo-900 dark:text-indigo-300 dark:hover:text-indigo-200"
                                        data-url="{{ $f['url'] }}"
                                        title="{{ __('site.admin_media_copy_url') }}"
                                    >{{ __('site.admin_media_copy_url') }}</button>
                                    <form action="{{ route('admin.media.destroy') }}" method="post" class="inline js-confirm-delete" data-confirm="{{ e(__('site.admin_delete_this_file')) }}">
                                        @csrf
                                        @method('delete')
                                        <input type="hidden" name="path" value="{{ $f['path'] }}">
                                        <x-admin.btn type="submit" variant="soft-danger" class="px-2 py-1 text-xs">{{ __('site.common_delete') }}</x-admin.btn>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('site.admin_media_no_files') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Lightbox Modal --}}
    <div id="media-lightbox" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4" role="dialog" aria-modal="true">
        <button id="lightbox-close" class="absolute right-4 top-4 text-white/80 hover:text-white text-3xl leading-none">&times;</button>
        <img id="lightbox-img" src="" alt="" class="max-h-[90vh] max-w-[90vw] rounded shadow-2xl object-contain" />
    </div>

    <div id="admin-media-i18n" hidden data-copied="{{ __('site.copied') }}"></div>
    @once
        @push('scripts')
            <script>
                const mediaI18n = document.getElementById('admin-media-i18n');
                let copiedText = mediaI18n ? (mediaI18n.getAttribute('data-copied') || '') : '';

                // Confirm delete
                document.querySelectorAll('form.js-confirm-delete').forEach(function (form) {
                    form.addEventListener('submit', function (e) {
                        if (!confirm(form.getAttribute('data-confirm'))) e.preventDefault();
                    });
                });

                // Copy URL
                document.querySelectorAll('.js-copy-url').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const url = btn.getAttribute('data-url');
                        if (!url) return;
                        const write = () => {
                            const orig = btn.textContent;
                            btn.textContent = '✓ ' + copiedText;
                            btn.classList.add('text-green-600');
                            setTimeout(() => { btn.textContent = orig; btn.classList.remove('text-green-600'); }, 2000);
                        };
                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(url).then(write);
                        } else {
                            const ta = document.createElement('textarea');
                            ta.value = url; ta.style.cssText = 'position:fixed;opacity:0';
                            document.body.appendChild(ta); ta.select(); document.execCommand('copy');
                            document.body.removeChild(ta); write();
                        }
                    });
                });

                // Lightbox
                const lightbox = document.getElementById('media-lightbox');
                const lightboxImg = document.getElementById('lightbox-img');

                document.querySelectorAll('.js-lightbox-open').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        lightboxImg.src = btn.getAttribute('data-url');
                        lightbox.classList.remove('hidden');
                        lightbox.classList.add('flex');
                    });
                });

                document.getElementById('lightbox-close').addEventListener('click', closeLightbox);
                lightbox.addEventListener('click', function (e) {
                    if (e.target === lightbox) closeLightbox();
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') closeLightbox();
                });

                function closeLightbox() {
                    lightbox.classList.add('hidden');
                    lightbox.classList.remove('flex');
                    lightboxImg.src = '';
                }
            </script>
        @endpush
    @endonce
</x-app-layout>
