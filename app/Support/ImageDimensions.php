<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class ImageDimensions
{
    /**
     * @return array{width: int, height: int}|null
     */
    public static function forStoragePublic(?string $relativePath): ?array
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($relativePath)) {
            return null;
        }

        $mtime = $disk->lastModified($relativePath);
        $full = $disk->path($relativePath);

        return SafeCache::remember(
            'img_dims:'.md5($relativePath).':'.$mtime,
            86400,
            function () use ($full) {
                $info = @getimagesize($full);

                return is_array($info) ? ['width' => (int) $info[0], 'height' => (int) $info[1]] : null;
            }
        );
    }
}
