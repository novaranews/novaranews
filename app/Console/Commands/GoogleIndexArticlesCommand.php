<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\GoogleIndexingApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GoogleIndexArticlesCommand extends Command
{
    protected $signature = 'google:index-articles
                            {--type=* : Content types (default: guide, analysis, review). Use --type=all for every type}
                            {--locale= : Limit processing to this locale (for example: tr, en)}
                            {--limit= : Maximum URLs to submit in this run (default: remaining daily quota)}
                            {--min-body= : Minimum plain-text body length in characters for filtering long-form content}
                            {--force : Resubmit URLs that were already reported or are still current}
                            {--sleep=1 : Delay between API calls in seconds}
                            {--dry-run : List eligible URLs without submitting them}';

    protected $description = 'Submit published article URLs to the Google Indexing API in bulk (default: long-form content types).';

    /** @var string[] */
    private const LONGFORM_TYPES = ['guide', 'analysis', 'review'];

    public function handle(GoogleIndexingApiService $indexing): int
    {
        if (! $indexing->isConfigured()) {
            $this->error('Google Indexing is not configured. Set the service-account JSON path with GOOGLE_INDEXING_CREDENTIALS_PATH.');

            return self::FAILURE;
        }

        if (! $indexing->isFeatureEnabled()) {
            $this->warn('GOOGLE_INDEXING_ENABLED=false — the CLI can still submit URLs, but the admin action is disabled.');
        }

        $types = $this->resolveTypes();
        $locale = $this->option('locale') ? trim((string) $this->option('locale')) : null;
        $minBody = $this->option('min-body') !== null ? max(0, (int) $this->option('min-body')) : 0;
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $sleep = max(0, (int) $this->option('sleep'));

        $remaining = $indexing->dailyRemaining();
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : $remaining;
        $budget = $dryRun ? PHP_INT_MAX : min($limit, $remaining);

        $this->line('Types: <info>'.implode(', ', $types).'</info>'
            .($locale ? "  Locale: <info>{$locale}</info>" : '')
            .($minBody > 0 ? "  Minimum body: <info>{$minBody}</info>" : ''));
        $this->line("Daily limit: {$indexing->dailyLimit()}  Used: {$indexing->dailyUsed()}  Remaining: {$remaining}");

        if (! $dryRun && $budget <= 0) {
            $this->warn('No quota remains for this run (the daily limit was reached or --limit=0).');

            return self::SUCCESS;
        }

        $query = Article::query()
            ->with('translations')
            ->published()
            ->whereIn('content_type', $types);

        if ($locale !== null && $locale !== '') {
            $query->where('locale', $locale);
        }

        $query->orderByDesc('is_editors_pick')
            ->orderByDesc('is_breaking')
            ->orderByDesc('published_at');

        $submitted = 0;
        $skipped = 0;
        $failed = 0;
        $rows = [];

        foreach ($query->cursor() as $article) {
            if (! $dryRun && $submitted >= $budget) {
                break;
            }

            $url = $this->eligibleUrl($article, $minBody);
            if ($url === null) {
                $skipped++;

                continue;
            }

            if (! $force && ! $article->googleIndexingNeedsNotify()) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $rows[] = [$article->id, $article->content_type, $article->locale, $url];
                $submitted++;

                continue;
            }

            $result = $indexing->publishUrlUpdated($url);

            if ($result['ok']) {
                DB::table('articles')->where('id', $article->id)->update([
                    'google_indexing_notified_at' => now(),
                ]);
                $submitted++;
                $this->line("  <info>OK</info>   #{$article->id}  {$url}");
            } elseif ($result['message'] === 'daily_limit') {
                $this->warn('The daily quota has been reached; stopping.');
                break;
            } else {
                $failed++;
                $this->line("  <error>FAIL</error> #{$article->id}  {$url}  — ".$result['message']);
            }

            if ($sleep > 0) {
                sleep($sleep);
            }
        }

        if ($dryRun) {
            if ($rows === []) {
                $this->warn('No eligible content was found.');
            } else {
                $this->table(['ID', 'Type', 'Locale', 'URL'], $rows);
                $this->info(count($rows).' URLs are eligible (dry run).');
            }

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info("Finished. Submitted: {$submitted}  Skipped: {$skipped}  Failed: {$failed}");

        return $failed > 0 && $submitted === 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function resolveTypes(): array
    {
        $opt = (array) $this->option('type');
        $opt = array_values(array_filter(array_map('trim', $opt)));

        if ($opt === []) {
            return self::LONGFORM_TYPES;
        }

        if (in_array('all', $opt, true)) {
            return Article::CONTENT_TYPES;
        }

        $valid = array_values(array_intersect($opt, Article::CONTENT_TYPES));

        return $valid !== [] ? $valid : self::LONGFORM_TYPES;
    }

    private function eligibleUrl(Article $article, int $minBody): ?string
    {
        if (! $article->isPublishedLive()) {
            return null;
        }

        $tr = $article->translations->firstWhere('locale', $article->locale);
        if (! $tr || ! filled($tr->slug) || ($tr->robots_noindex ?? false)) {
            return null;
        }

        if ($minBody > 0) {
            $plain = trim(html_entity_decode(strip_tags((string) $tr->getRawOriginal('body'))));
            if (mb_strlen($plain) < $minBody) {
                return null;
            }
        }

        $url = $article->publicUrl($article->locale);
        if ($url === null || $url === '' || $url === '#' || ! str_starts_with($url, 'https://')) {
            return null;
        }

        return $url;
    }
}
