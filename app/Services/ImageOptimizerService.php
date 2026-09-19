<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Optimize images stored on the public disk:
 *  - Resize down to a max width/height (keeping aspect ratio)
 *  - Prefer WebP output when GD supports it (smaller files, better LCP)
 *  - Fall back to JPEG recompression when WebP is unavailable
 *
 * Requires the GD extension (php8.x-gd on the server).
 */
class ImageOptimizerService
{
    private const MAX_WIDTH = 1920;

    private const MAX_HEIGHT = 1080;

    private const JPEG_QUALITY = 82;

    // 78 → visually lossless (SSIM ≈ 0.99+) while typically 30-40% smaller than q=85.
    private const WEBP_QUALITY = 78;

    /**
     * Already-optimized WebP files smaller than this threshold (bytes) are skipped
     * during bulk re-optimization to prevent lossy-on-lossy generation loss.
     * 200 KB is a generous ceiling for a 1920×1080 WebP at q=78.
     */
    private const WEBP_SKIP_THRESHOLD = 204_800; // 200 KB

    /**
     * Thumbnail max width for srcset (mobile-first responsive images).
     * 600px covers non-retina mobile full-width and retina half-width cards.
     */
    public const THUMB_WIDTH = 600;

    /** Subdirectory (relative to the media folder) where thumbnails are stored. */
    public const THUMB_SUBDIR = 'thumbs';

    /** Google News recommended featured image dimensions */
    public const GN_WIDTH  = 1200;
    public const GN_HEIGHT = 628;

    /**
     * Optimize a file already stored on the public disk.
     * May replace the file with a .webp variant and remove the original.
     *
     * @return string|false Final relative storage path, or false on failure/skip
     */
    public function optimizeStored(string $storagePath): string|false
    {
        if (! extension_loaded('gd')) {
            return false;
        }

        $absolutePath = Storage::disk('public')->path($storagePath);

        if (! is_file($absolutePath)) {
            return false;
        }

        try {
            $result = $this->processFile($absolutePath, $storagePath);

            return $result ?? false;
        } catch (\Throwable $e) {
            Log::warning("ImageOptimizer: failed for {$storagePath}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Generate a thumbnail (≤ THUMB_WIDTH wide) for a file already on the public disk.
     * The thumbnail is written to <mediaDir>/thumbs/<filename>.webp alongside the original.
     *
     * Returns the thumbnail's storage path, or false on failure / GD unavailable.
     */
    public function generateThumbnail(string $storagePath): string|false
    {
        if (! extension_loaded('gd')) {
            return false;
        }

        $absolutePath = Storage::disk('public')->path($storagePath);
        if (! is_file($absolutePath)) {
            return false;
        }

        try {
            $info = @getimagesize($absolutePath);
            if (! $info) {
                return false;
            }

            [, , $type] = $info;

            // Skip non-raster formats
            if (! in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
                return false;
            }

            // Skip if already smaller than or equal to thumb width
            if ($info[0] <= self::THUMB_WIDTH) {
                return false;
            }

            $src = match ($type) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($absolutePath),
                IMAGETYPE_PNG  => @imagecreatefrompng($absolutePath),
                IMAGETYPE_WEBP => @imagecreatefromwebp($absolutePath),
                IMAGETYPE_GIF  => @imagecreatefromgif($absolutePath),
            };

            if ($src === false) {
                return false;
            }

            $srcW = imagesx($src);
            $srcH = imagesy($src);
            $scale = self::THUMB_WIDTH / $srcW;
            $newW = self::THUMB_WIDTH;
            $newH = (int) round($srcH * $scale);

            $dst = imagecreatetruecolor($newW, $newH);
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
            imagedestroy($src);

            // Store at <dir>/thumbs/<base>.webp
            $dir  = dirname($storagePath);
            $base = pathinfo($storagePath, PATHINFO_FILENAME);
            $thumbDir = ($dir === '.' || $dir === '') ? self::THUMB_SUBDIR : $dir.'/'.self::THUMB_SUBDIR;
            $thumbRelative = $thumbDir.'/'.$base.'.webp';
            $thumbAbsolute = Storage::disk('public')->path($thumbRelative);

            // Ensure the thumbs directory exists
            @mkdir(dirname($thumbAbsolute), 0755, true);

            if (function_exists('imagewebp') && imagewebp($dst, $thumbAbsolute, self::WEBP_QUALITY)) {
                imagedestroy($dst);
                return $thumbRelative;
            }

            imagedestroy($dst);
            return false;

        } catch (\Throwable $e) {
            Log::warning("ImageOptimizer: thumbnail failed for {$storagePath}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Center-crop a stored image to Google News dimensions (1200×628, ~16:9).
     * Converts to WebP and replaces the original file.
     * Returns the final storage path, or the original path on failure.
     */
    public function cropToGoogleNews(string $storagePath): string
    {
        if (! extension_loaded('gd')) {
            return $storagePath;
        }

        try {
            $absolutePath = Storage::disk('public')->path($storagePath);
            if (! is_file($absolutePath)) {
                return $storagePath;
            }

            $info = @getimagesize($absolutePath);
            if (! $info) {
                return $storagePath;
            }

            [, , $type] = $info;
            $src = match ($type) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($absolutePath),
                IMAGETYPE_PNG  => @imagecreatefrompng($absolutePath),
                IMAGETYPE_WEBP => @imagecreatefromwebp($absolutePath),
                IMAGETYPE_GIF  => @imagecreatefromgif($absolutePath),
                default        => false,
            };

            if ($src === false) {
                return $storagePath;
            }

            $srcW = imagesx($src);
            $srcH = imagesy($src);

            // Determine crop box: center-crop to target aspect ratio first
            $targetRatio = self::GN_WIDTH / self::GN_HEIGHT;
            $srcRatio    = $srcW / max(1, $srcH);

            if ($srcRatio > $targetRatio) {
                // Source is wider — crop width
                $cropH = $srcH;
                $cropW = (int) round($srcH * $targetRatio);
                $cropX = (int) round(($srcW - $cropW) / 2);
                $cropY = 0;
            } else {
                // Source is taller — crop height
                $cropW = $srcW;
                $cropH = (int) round($srcW / $targetRatio);
                $cropX = 0;
                $cropY = (int) round(($srcH - $cropH) / 2);
            }

            $dst = imagecreatetruecolor(self::GN_WIDTH, self::GN_HEIGHT);
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);

            imagecopyresampled(
                $dst, $src,
                0, 0,
                $cropX, $cropY,
                self::GN_WIDTH, self::GN_HEIGHT,
                $cropW, $cropH
            );
            imagedestroy($src);

            // Save as WebP replacing original
            $dir          = dirname($storagePath);
            $base         = pathinfo($storagePath, PATHINFO_FILENAME);
            $webpRelative = ($dir === '.' || $dir === '') ? $base.'.webp' : $dir.'/'.$base.'.webp';
            $webpAbsolute = Storage::disk('public')->path($webpRelative);

            if (function_exists('imagewebp') && imagewebp($dst, $webpAbsolute, self::WEBP_QUALITY)) {
                imagedestroy($dst);
                if ($webpRelative !== $storagePath && is_file($absolutePath)) {
                    @unlink($absolutePath);
                }
                return $webpRelative;
            }

            // WebP unavailable — save as JPEG
            imagejpeg($dst, $absolutePath, self::JPEG_QUALITY);
            imagedestroy($dst);

            return $storagePath;

        } catch (\Throwable $e) {
            Log::warning("ImageOptimizer cropToGoogleNews failed for {$storagePath}: {$e->getMessage()}");
            return $storagePath;
        }
    }

    /**
     * Optimize a raw image binary string (not yet on disk).
     * Returns JPEG binary (WebP not applied to in-memory pipeline).
     */
    public function optimizeRaw(string $binary): string
    {
        if (! extension_loaded('gd')) {
            return $binary;
        }

        try {
            $src = @imagecreatefromstring($binary);
            if ($src === false) {
                return $binary;
            }

            $src = $this->resize($src);

            ob_start();
            imagejpeg($src, null, self::JPEG_QUALITY);
            $result = ob_get_clean();
            imagedestroy($src);

            return $result ?: $binary;
        } catch (\Throwable) {
            return $binary;
        }
    }

    private function processFile(string $absolutePath, string $storagePath): ?string
    {
        $info = @getimagesize($absolutePath);
        if (! $info) {
            return null;
        }

        [, , $type] = $info;

        // Skip WebP files that are already small enough — re-compressing a lossy
        // format introduces generation loss without meaningful size savings.
        // Large WebP files (above threshold) are still recompressed because they
        // were likely uploaded at high quality and can be shrunken significantly.
        if ($type === IMAGETYPE_WEBP
            && filesize($absolutePath) <= self::WEBP_SKIP_THRESHOLD
            && $info[0] <= self::MAX_WIDTH
            && $info[1] <= self::MAX_HEIGHT
        ) {
            return $storagePath;
        }

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absolutePath),
            IMAGETYPE_PNG  => @imagecreatefrompng($absolutePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($absolutePath),
            IMAGETYPE_GIF  => @imagecreatefromgif($absolutePath),
            default        => false,
        };

        if ($src === false) {
            return null;
        }

        $img = $this->resize($src);

        $dir = dirname($storagePath);
        $base = pathinfo($storagePath, PATHINFO_FILENAME);
        $webpRelative = ($dir === '.' || $dir === '') ? $base.'.webp' : $dir.'/'.$base.'.webp';
        $webpAbsolute = Storage::disk('public')->path($webpRelative);

        if (function_exists('imagewebp') && imagewebp($img, $webpAbsolute, self::WEBP_QUALITY)) {
            imagedestroy($img);
            if ($webpRelative !== $storagePath && is_file($absolutePath)) {
                @unlink($absolutePath);
            }

            return $webpRelative;
        }

        $saved = imagejpeg($img, $absolutePath, self::JPEG_QUALITY);
        imagedestroy($img);

        return $saved ? $storagePath : null;
    }

    private function resize(\GdImage $src): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);

        $scale = 1.0;
        if ($w > self::MAX_WIDTH) {
            $scale = self::MAX_WIDTH / $w;
        }
        if ($h * $scale > self::MAX_HEIGHT) {
            $scale = self::MAX_HEIGHT / $h;
        }

        if ($scale >= 1.0) {
            return $src;
        }

        $newW = (int) round($w * $scale);
        $newH = (int) round($h * $scale);

        $dst = imagecreatetruecolor($newW, $newH);

        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($src);

        return $dst;
    }
}
