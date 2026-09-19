<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ImageOptimizerService;

class ImageSearchService
{
    private const UNSPLASH_API = 'https://api.unsplash.com';

    /**
     * Search Unsplash for a relevant image, download and store it.
     *
     * @return array{path: ?string, alt: string, caption: ?string, original_url: ?string}
     */
    public function search(string $query, string $locale = 'en'): array
    {
        $key = config('services.unsplash.access_key');

        if (! $key) {
            return $this->empty($query);
        }

        try {
            return $this->searchWithQuery($this->extractKeywords($query), $key);
        } catch (\Throwable $e) {
            Log::warning("ImageSearchService error: {$e->getMessage()}");
            return $this->empty($query);
        }
    }

    /**
     * Internal search with a pre-built query string.
     */
    private function searchWithQuery(string $keywords, string $key): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Client-ID {$key}",
                'Accept-Version' => 'v1',
            ])->timeout(15)->get(self::UNSPLASH_API.'/search/photos', [
                'query' => $keywords,
                'per_page' => 5,
                'orientation' => 'landscape',
                'content_filter' => 'high',
            ]);

            if (! $response->successful()) {
                Log::warning("Unsplash API error {$response->status()} for query: {$keywords}");
                return $this->empty($keywords);
            }

            $results = $response->json('results', []);

            if (empty($results)) {
                // Fallback: retry with first 2 words only (broader search)
                $words         = explode(' ', $keywords);
                $fallbackQuery = implode(' ', array_slice($words, 0, 2));
                if ($fallbackQuery && $fallbackQuery !== $keywords && count($words) > 2) {
                    return $this->searchWithQuery($fallbackQuery, $key);
                }
                return $this->empty($keywords);
            }

            // Pick randomly from top 3 results for variety (not always the same first photo)
            $topResults = array_slice($results, 0, min(3, count($results)));
            $photo      = $topResults[array_rand($topResults)];

            // Build a 1280px-wide URL via Unsplash's imgix pipeline (raw + w param).
            // 'regular' is ~1080px which fails the 1200px Google News minimum width check.
            // 'full' is original size (often 4-6MB). Using raw+w=1280 gives quality + manageable size.
            $rawUrl = $photo['urls']['raw'] ?? null;
            $imageUrl = $rawUrl
                ? rtrim($rawUrl, '&?').'&w=1280&q=85&fm=jpg&fit=max'
                : ($photo['urls']['full'] ?? $photo['urls']['regular'] ?? null);

            if (! $imageUrl) {
                return $this->empty($keywords);
            }

            // Download and store the image
            $path = $this->downloadImage($imageUrl);

            $alt = $photo['alt_description'] ?? $photo['description'] ?? $keywords;
            $photographer = $photo['user']['name'] ?? null;
            $caption = $photographer ? "Photo by {$photographer} on Unsplash" : null;

            // Trigger download event on Unsplash (required by API terms)
            $downloadLocation = $photo['links']['download_location'] ?? null;
            if ($downloadLocation) {
                Http::withHeaders(['Authorization' => "Client-ID {$key}"])->timeout(5)->get($downloadLocation);
            }

            return [
                'path' => $path,
                'alt' => mb_substr((string) $alt, 0, 255),
                'caption' => $caption,
                'original_url' => $imageUrl,
            ];

        } catch (\Throwable $e) {
            Log::warning("ImageSearchService error: {$e->getMessage()}");
            return $this->empty($keywords);
        }
    }

    private function downloadImage(string $url): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $ext = 'jpg';
            $contentType = $response->header('Content-Type') ?? '';
            if (str_contains($contentType, 'png')) {
                $ext = 'png';
            } elseif (str_contains($contentType, 'webp')) {
                $ext = 'webp';
            }

            $filename = 'media/ai-'.Str::random(16).'.'.$ext;
            Storage::disk('public')->put($filename, $response->body());

            // Center-crop to 1200×628 (Google News 16:9) and convert to WebP.
            $optimizer = app(ImageOptimizerService::class);
            $optimized = $optimizer->cropToGoogleNews($filename);
            $finalPath = is_string($optimized) ? $optimized : $filename;

            // Generate mobile thumbnail for srcset support.
            $optimizer->generateThumbnail($finalPath);

            return $finalPath;

        } catch (\Throwable $e) {
            Log::warning("ImageSearchService: download failed: {$e->getMessage()}");
            return null;
        }
    }

    private function extractKeywords(string $title): string
    {
        // Remove common stop words, keep meaningful keywords
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
            'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were', 'be', 'been',
            'has', 'have', 'had', 'will', 'would', 'could', 'should', 'may', 'might',
            'die', 'der', 'das', 'und', 'ist', 'ein', 'eine', 'le', 'la', 'les', 'un',
            'que', 'de', 'el', 'los', 'las', 'bir', 've', 'bu', 'ile', 'için'];

        $words = preg_split('/\s+/', strtolower(strip_tags($title)));
        $keywords = array_filter($words ?? [], fn ($w) => strlen($w) > 3 && ! in_array($w, $stopWords, true));
        $keywords = array_slice(array_values($keywords), 0, 4);

        return implode(' ', $keywords) ?: $title;
    }

    /**
     * @return array{path: null, alt: string, caption: null, original_url: null}
     */
    private function empty(string $query): array
    {
        return ['path' => null, 'alt' => $query, 'caption' => null, 'original_url' => null];
    }
}
