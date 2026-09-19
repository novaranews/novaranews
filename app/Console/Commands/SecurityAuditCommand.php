<?php

namespace App\Console\Commands;

use App\Models\ArticleTranslation;
use App\Models\SeoRedirect;
use App\Models\StaticPageTranslation;
use App\Support\SafeRedirectUrl;
use Illuminate\Console\Command;

class SecurityAuditCommand extends Command
{
    protected $signature = 'security:audit
                            {--limit=200 : Max suspicious rows to print}
                            {--fix-external-redirects : Disable active redirect rows that point outside this site}';

    protected $description = 'Audit redirects and content for deceptive-page indicators';

    private const SUSPICIOUS_PATTERN = '/(javascript:|vbscript:|data:text\/html|<script\b|<iframe\b|onerror\s*=|onload\s*=)/i';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        $this->info('Security audit started.');
        $this->line('App host: '.($appHost !== '' ? $appHost : '[missing app.url host]'));

        $externalRedirects = $this->findExternalRedirects();
        $articleHits = $this->findSuspiciousArticleBodies($limit);
        $staticPageHits = $this->findSuspiciousStaticPageContent($limit);

        $this->newLine();
        $this->warn('Findings summary');
        $this->line('- External active redirects: '.count($externalRedirects));
        $this->line('- Suspicious article bodies: '.count($articleHits));
        $this->line('- Suspicious static page content: '.count($staticPageHits));

        if ($externalRedirects !== []) {
            $this->newLine();
            $this->warn('External redirect rows');
            $this->table(
                ['id', 'locale', 'from_path', 'to_url', 'status', 'active'],
                array_map(static fn (SeoRedirect $row): array => [
                    (string) $row->id,
                    (string) ($row->locale ?? '*'),
                    (string) $row->from_path,
                    (string) $row->to_url,
                    (string) $row->status_code,
                    $row->is_active ? '1' : '0',
                ], $externalRedirects)
            );
        }

        if ($articleHits !== []) {
            $this->newLine();
            $this->warn('Suspicious article body rows');
            $this->table(
                ['id', 'article_id', 'locale', 'slug', 'excerpt'],
                array_map(static fn (ArticleTranslation $row): array => [
                    (string) $row->id,
                    (string) $row->article_id,
                    (string) $row->locale,
                    (string) $row->slug,
                    mb_substr(trim(strip_tags((string) $row->body)), 0, 120),
                ], $articleHits)
            );
        }

        if ($staticPageHits !== []) {
            $this->newLine();
            $this->warn('Suspicious static page rows');
            $this->table(
                ['id', 'page_id', 'locale', 'slug', 'excerpt'],
                array_map(static fn (StaticPageTranslation $row): array => [
                    (string) $row->id,
                    (string) $row->static_page_id,
                    (string) $row->locale,
                    (string) ($row->slug ?? ''),
                    mb_substr(trim(strip_tags((string) $row->content)), 0, 120),
                ], $staticPageHits)
            );
        }

        if ($this->option('fix-external-redirects')) {
            $fixedCount = 0;
            foreach ($externalRedirects as $row) {
                if ($row->is_active) {
                    $row->is_active = false;
                    $row->save();
                    $fixedCount++;
                }
            }

            $this->newLine();
            $this->info("Disabled {$fixedCount} external redirect row(s).");
        } elseif ($externalRedirects !== []) {
            $this->newLine();
            $this->line('Use --fix-external-redirects to disable external redirect rows automatically.');
        }

        $hasFindings = $externalRedirects !== [] || $articleHits !== [] || $staticPageHits !== [];

        return $hasFindings ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<SeoRedirect>
     */
    private function findExternalRedirects(): array
    {
        $rows = SeoRedirect::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $external = [];
        foreach ($rows as $row) {
            if (SafeRedirectUrl::normalizeInternal((string) $row->to_url) === null) {
                $external[] = $row;
            }
        }

        return $external;
    }

    /**
     * @return list<ArticleTranslation>
     */
    private function findSuspiciousArticleBodies(int $limit): array
    {
        $hits = [];
        $rows = ArticleTranslation::query()
            ->select(['id', 'article_id', 'locale', 'slug', 'body'])
            ->orderBy('id')
            ->cursor();

        foreach ($rows as $row) {
            if (preg_match(self::SUSPICIOUS_PATTERN, (string) $row->body) === 1) {
                $hits[] = $row;
                if (count($hits) >= $limit) {
                    break;
                }
            }
        }

        return $hits;
    }

    /**
     * @return list<StaticPageTranslation>
     */
    private function findSuspiciousStaticPageContent(int $limit): array
    {
        $hits = [];
        $rows = StaticPageTranslation::query()
            ->select(['id', 'static_page_id', 'locale', 'slug', 'content'])
            ->orderBy('id')
            ->cursor();

        foreach ($rows as $row) {
            if (preg_match(self::SUSPICIOUS_PATTERN, (string) ($row->content ?? '')) === 1) {
                $hits[] = $row;
                if (count($hits) >= $limit) {
                    break;
                }
            }
        }

        return $hits;
    }
}

