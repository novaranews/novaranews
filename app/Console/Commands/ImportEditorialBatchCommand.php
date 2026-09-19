<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\ArticleTranslation;
use App\Support\ArticleSources;
use App\Support\SiteCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Stevebauman\Purify\Facades\Purify;

class ImportEditorialBatchCommand extends Command
{
    protected $signature = 'content:import-editorial-batch
                            {path : JSON batch file path}
                            {--apply : Persist the batch. Without this flag the command only validates and previews.}
                            {--user-id= : Optional admin user id for revisions/audit logs}';

    protected $description = 'Validate and import editor-reviewed article content batches';

    /**
     * @var list<string>
     */
    private array $errors = [];

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        $apply = (bool) $this->option('apply');
        $userId = $this->option('user-id') !== null && $this->option('user-id') !== ''
            ? (int) $this->option('user-id')
            : null;

        if (! is_file($path)) {
            $this->error("Batch file not found: {$path}");

            return self::FAILURE;
        }

        $payload = json_decode((string) file_get_contents($path), true);
        if (! is_array($payload)) {
            $this->error('Batch file is not valid JSON.');

            return self::FAILURE;
        }

        $items = $this->extractItems($payload);
        if ($items === []) {
            $this->error('Batch has no article items.');

            return self::FAILURE;
        }

        $prepared = [];
        foreach ($items as $index => $item) {
            $row = $this->prepareItem($item, $index);
            if ($row !== null) {
                $prepared[] = $row;
            }
        }

        if ($this->errors !== []) {
            $this->newLine();
            $this->error('Batch validation failed:');
            foreach ($this->errors as $error) {
                $this->line('- '.$error);
            }

            return self::FAILURE;
        }

        $this->info($apply ? 'Editorial batch apply mode' : 'Editorial batch dry-run mode');
        $this->table(
            ['id', 'locale', 'decision', 'title', 'words', 'sources', 'source URLs', 'reviewed', 'clear AI'],
            array_map(static fn (array $row): array => [
                (string) $row['article']->id,
                (string) $row['translation']->locale,
                (string) $row['decision'],
                mb_substr((string) $row['updates']['title'], 0, 52),
                (string) $row['word_count'],
                (string) count($row['updates']['sources']),
                (string) count(ArticleSources::urls($row['updates']['sources'])),
                $row['mark_reviewed'] ? 'yes' : 'no',
                $row['clear_ai_flag'] ? 'yes' : 'no',
            ], $prepared)
        );

        if (! $apply) {
            $this->newLine();
            $this->warn('No database changes were made. Re-run with --apply to persist this batch.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($prepared, $userId): void {
            foreach ($prepared as $row) {
                /** @var Article $article */
                $article = $row['article'];
                /** @var ArticleTranslation $translation */
                $translation = $row['translation'];

                ArticleRevision::query()->create([
                    'article_id' => $article->id,
                    'article_translation_id' => $translation->id,
                    'user_id' => $userId,
                    'locale' => $translation->locale,
                    'reason' => 'editorial_batch_before_update',
                    'snapshot' => [
                        'title' => $translation->getRawOriginal('title'),
                        'excerpt' => $translation->getRawOriginal('excerpt'),
                        'body' => $translation->getRawOriginal('body'),
                        'meta_title' => $translation->getRawOriginal('meta_title'),
                        'meta_description' => $translation->getRawOriginal('meta_description'),
                        'og_title' => $translation->getRawOriginal('og_title'),
                        'og_description' => $translation->getRawOriginal('og_description'),
                        'robots_noindex' => $translation->robots_noindex,
                        'robots_nofollow' => $translation->robots_nofollow,
                        'sources' => $translation->sources,
                    ],
                ]);

                $translation->forceFill($row['updates'])->save();

                $articleUpdates = [];
                if ($row['mark_reviewed']) {
                    $articleUpdates['editor_reviewed_at'] = now();
                }
                if ($row['clear_ai_flag']) {
                    $articleUpdates['is_ai_generated'] = false;
                }
                if ($articleUpdates !== []) {
                    $article->forceFill($articleUpdates)->save();
                } else {
                    $article->touch();
                }
            }
        });

        SiteCache::forgetHomeAndNav();

        $this->newLine();
        $this->info('Editorial batch imported successfully.');

        return self::SUCCESS;
    }

    /**
     * @param  array<mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function extractItems(array $payload): array
    {
        if (array_is_list($payload)) {
            return $payload;
        }

        $items = $payload['articles'] ?? $payload['items'] ?? null;

        return is_array($items) && array_is_list($items) ? $items : [];
    }

    /**
     * @param  array<string, mixed>|mixed  $item
     * @return array<string, mixed>|null
     */
    private function prepareItem(mixed $item, int $index): ?array
    {
        $label = 'item '.($index + 1);
        if (! is_array($item)) {
            $this->errors[] = "{$label}: item must be an object.";

            return null;
        }

        $id = (int) ($item['id'] ?? 0);
        $locale = trim((string) ($item['locale'] ?? ''));
        if ($id <= 0) {
            $this->errors[] = "{$label}: id is required.";

            return null;
        }
        if ($locale === '') {
            $this->errors[] = "{$label}: locale is required.";

            return null;
        }

        $article = Article::query()->with('translations')->find($id);
        if (! $article) {
            $this->errors[] = "{$label}: article {$id} not found.";

            return null;
        }

        $translation = $article->translations->firstWhere('locale', $locale);
        if (! $translation) {
            $this->errors[] = "{$label}: article {$id} has no {$locale} translation.";

            return null;
        }

        $title = trim((string) ($item['title'] ?? ''));
        $body = trim((string) ($item['body'] ?? ''));
        if ($title === '') {
            $this->errors[] = "{$label}: title is required.";
        }
        if ($body === '') {
            $this->errors[] = "{$label}: body is required.";
        }

        $sources = ArticleSources::normalize($item['sources'] ?? []);
        if ($sources === []) {
            $this->errors[] = "{$label}: at least one source is required.";
        }
        if (count(ArticleSources::urls($sources)) === 0) {
            $this->errors[] = "{$label}: at least one source URL is required.";
        }

        $wordCount = $this->wordCount($body);
        if ($wordCount < 450 && ! (bool) ($item['allow_short_body'] ?? false)) {
            $this->errors[] = "{$label}: body has {$wordCount} words; use allow_short_body=true only for intentional short news.";
        }

        $decision = trim((string) ($item['editor_decision'] ?? 'keep_index'));
        if (! in_array($decision, ['keep_index', 'noindex', 'merge_later'], true)) {
            $this->errors[] = "{$label}: editor_decision must be keep_index, noindex, or merge_later.";
        }

        $robotsNoindex = array_key_exists('robots_noindex', $item)
            ? (bool) $item['robots_noindex']
            : $translation->robots_noindex;
        if ($decision === 'noindex') {
            $robotsNoindex = true;
        } elseif ($decision === 'keep_index' && array_key_exists('robots_noindex', $item)) {
            $robotsNoindex = (bool) $item['robots_noindex'];
        }

        return [
            'article' => $article,
            'translation' => $translation,
            'decision' => $decision,
            'word_count' => $wordCount,
            'mark_reviewed' => (bool) ($item['mark_editor_reviewed'] ?? false),
            'clear_ai_flag' => (bool) ($item['clear_ai_flag'] ?? false),
            'updates' => [
                'title' => $title,
                'excerpt' => $this->nullableString($item['excerpt'] ?? null),
                'body' => Purify::clean($body),
                'meta_title' => $this->nullableString($item['meta_title'] ?? null),
                'meta_description' => $this->nullableString($item['meta_description'] ?? null),
                'og_title' => $this->nullableString($item['og_title'] ?? $item['meta_title'] ?? null),
                'og_description' => $this->nullableString($item['og_description'] ?? $item['meta_description'] ?? null),
                'robots_noindex' => $robotsNoindex,
                'robots_nofollow' => array_key_exists('robots_nofollow', $item)
                    ? (bool) $item['robots_nofollow']
                    : $translation->robots_nofollow,
                'sources' => $sources,
            ],
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function wordCount(string $html): int
    {
        $text = trim((string) preg_replace('/\s+/', ' ', strip_tags($html)));
        if ($text === '') {
            return 0;
        }

        preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);

        return count($matches[0] ?? []);
    }
}
