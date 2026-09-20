<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageOptimizerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    public function index(): View
    {
        $disk = Storage::disk('public');
        $subdir = config('novaranews.public_upload_subdir', 'media');
        $paths = $disk->exists($subdir) ? $disk->allFiles($subdir) : [];
        $thumbSegment = '/'.ImageOptimizerService::THUMB_SUBDIR.'/';
        $files = collect($paths)
            ->filter(function (string $path) use ($thumbSegment) {
                // Exclude auto-generated thumbnail files from the media library listing.
                return ! str_contains(str_replace('\\', '/', $path), $thumbSegment);
            })
            ->map(function (string $path) use ($disk) {
                return [
                    'path' => $path,
                    'url' => $disk->url($path),
                    'size' => $disk->size($path),
                    'modified' => $disk->lastModified($path),
                ];
            })
            ->sortByDesc('modified')
            ->values();

        return view('admin.media.index', compact('files'));
    }

    public function store(Request $request, ImageOptimizerService $optimizer): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $path = $request->file('file')->store(
            config('novaranews.public_upload_subdir', 'media'),
            'public'
        );

        $finalPath = $optimizer->optimizeStored($path) ?: $path;
        $optimizer->generateThumbnail($finalPath);

        return redirect()->route('admin.media.index')->with('success', __('File uploaded.'));
    }

    public function storeForEditor(Request $request, ImageOptimizerService $optimizer): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $path = $request->file('file')->store(
            config('novaranews.public_upload_subdir', 'media'),
            'public'
        );

        $optimized = $optimizer->optimizeStored($path);
        $finalPath = $optimized ?: $path;
        $optimizer->generateThumbnail($finalPath);

        return response()->json([
            'location' => Storage::disk('public')->url($finalPath),
        ]);
    }

    public function optimizeAll(ImageOptimizerService $optimizer): RedirectResponse
    {
        $disk = Storage::disk('public');
        $subdir = config('novaranews.public_upload_subdir', 'media');
        $files = $disk->exists($subdir) ? $disk->allFiles($subdir) : [];

        $optimized = 0;
        $urlMap = []; // ['old_url' => 'new_url']

        foreach ($files as $path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            // Process raster images including existing .webp files (re-compress at current quality).
            // Skip files inside the thumbs subdirectory — they are generated automatically.
            if (! in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
                continue;
            }
            if (str_contains(str_replace('\\', '/', $path), '/'.ImageOptimizerService::THUMB_SUBDIR.'/')) {
                continue;
            }

            $oldUrl = $disk->url($path);
            $result = $optimizer->optimizeStored($path);

            if ($result !== false) {
                $newUrl = $disk->url($result);
                if ($newUrl !== $oldUrl) {
                    $urlMap[$oldUrl] = $newUrl;
                }
                $optimized++;
                // Re-generate thumbnail for the final (possibly renamed) file
                $optimizer->generateThumbnail($result);
            }
        }

        // Update article body content to point to new WebP URLs
        if (! empty($urlMap)) {
            \App\Models\ArticleTranslation::query()
                ->whereNotNull('body')
                ->chunkById(100, function ($translations) use ($urlMap) {
                    foreach ($translations as $translation) {
                        $newBody = str_replace(
                            array_keys($urlMap),
                            array_values($urlMap),
                            $translation->body
                        );
                        if ($newBody !== $translation->body) {
                            $translation->updateQuietly(['body' => $newBody]);
                        }
                    }
                });
        }

        return redirect()->route('admin.media.index')
            ->with('success', "{$optimized} images were optimized, thumbnails were regenerated, and article content was updated.");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $subdir = preg_quote(config('novaranews.public_upload_subdir', 'media'), '/');
        $validated = $request->validate([
            'path' => ['required', 'string', 'regex:/^'.$subdir.'\/[a-zA-Z0-9._\/-]+$/'],
        ]);

        $path = $validated['path'];
        $this->assertMediaPathInsideUploadRoot($path);

        if (! Storage::disk('public')->exists($path)) {
            return redirect()->route('admin.media.index')->with('error', __('File not found.'));
        }

        Storage::disk('public')->delete($path);

        // Also delete the auto-generated thumbnail if it exists.
        $dir  = dirname($path);
        $base = pathinfo($path, PATHINFO_FILENAME);
        $thumbDir = ($dir === '.' || $dir === '') ? ImageOptimizerService::THUMB_SUBDIR : $dir.'/'.ImageOptimizerService::THUMB_SUBDIR;
        $thumbPath = $thumbDir.'/'.$base.'.webp';
        if (Storage::disk('public')->exists($thumbPath)) {
            Storage::disk('public')->delete($thumbPath);
        }

        return redirect()->route('admin.media.index')->with('success', __('File deleted.'));
    }

    /**
     * Block path traversal (e.g. media/foo/../../.env) even if the regex matched.
     */
    private function assertMediaPathInsideUploadRoot(string $path): void
    {
        $normalized = str_replace('\\', '/', $path);
        if ($normalized === '' || str_contains($normalized, '..')) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $subdir = trim((string) config('novaranews.public_upload_subdir', 'media'), '/');
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return;
        }

        $full = $disk->path($path);
        $base = $disk->path($subdir);
        $realFull = realpath($full);
        $realBase = realpath($base);

        if ($realFull === false || $realBase === false) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($realFull !== $realBase && ! str_starts_with($realFull, $realBase.DIRECTORY_SEPARATOR)) {
            abort(Response::HTTP_FORBIDDEN);
        }
    }
}
