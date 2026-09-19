<?php

namespace App\Console\Commands;

use App\Jobs\GenerateArticleJob;
use App\Models\AiArticleGeneration;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Console\Command;

class GenerateArticlesCommand extends Command
{
    protected $signature = 'news:generate
                            {--limit=10 : Maximum number of pending items to dispatch}
                            {--sync : Run synchronously instead of dispatching to queue}';

    protected $description = 'Dispatch GenerateArticleJob for pending AI article generations';

    public function handle(): int
    {
        if (! Setting::get('bot_enabled', true)) {
            $this->info('Bot is disabled in settings. Skipping.');
            return self::SUCCESS;
        }

        $limitFromSettings = (int) Setting::get('bot_articles_per_run', 20);
        $limit = max(1, (int) $this->option('limit') ?: $limitFromSettings);

        // Pick pending items balanced across all categories (round-robin)
        $pending = $this->pickBalanced($limit);

        if ($pending->isEmpty()) {
            $this->info('No pending article generations.');
            return self::SUCCESS;
        }

        $this->info("Processing {$pending->count()} pending generation(s)...");

        if ($this->option('sync')) {
            $service = app(\App\Services\ClaudeArticleGeneratorService::class);
            foreach ($pending as $gen) {
                $this->line("  Generating: {$gen->source_title}");
                $service->process($gen);
                $gen->refresh();
                $status = $gen->status === AiArticleGeneration::STATUS_DONE ? '<info>done</info>' : '<error>failed</error>';
                $this->line("  Status: {$status}");
            }
        } else {
            foreach ($pending as $gen) {
                GenerateArticleJob::dispatch($gen->id);
            }
            $this->info("Dispatched {$pending->count()} job(s) to queue.");
        }

        return self::SUCCESS;
    }

    /**
     * Select pending items distributed evenly across all 8 categories (round-robin).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function pickBalanced(int $limit): \Illuminate\Database\Eloquent\Collection
    {
        $categoryKeys = Category::orderedKeys();

        if ($categoryKeys->isEmpty()) {
            return AiArticleGeneration::query()
                ->where('status', AiArticleGeneration::STATUS_PENDING)
                ->oldest()
                ->limit($limit)
                ->get();
        }

        $rounds     = (int) ceil($limit / $categoryKeys->count());
        $selectedIds = collect();

        foreach (range(1, $rounds) as $round) {
            foreach ($categoryKeys as $key) {
                if ($selectedIds->count() >= $limit) {
                    break 2;
                }

                $id = AiArticleGeneration::query()
                    ->where('status', AiArticleGeneration::STATUS_PENDING)
                    ->where('source_packets->category_key', $key)
                    ->whereNotIn('id', $selectedIds->all())
                    ->oldest()
                    ->value('id');

                if ($id !== null) {
                    $selectedIds->push($id);
                }
            }
        }

        // Fill remaining slots if any category was empty
        if ($selectedIds->count() < $limit) {
            $extra = AiArticleGeneration::query()
                ->where('status', AiArticleGeneration::STATUS_PENDING)
                ->whereNotIn('id', $selectedIds->all())
                ->oldest()
                ->limit($limit - $selectedIds->count())
                ->pluck('id');

            $selectedIds = $selectedIds->merge($extra);
        }

        return AiArticleGeneration::query()
            ->whereIn('id', $selectedIds->all())
            ->get();
    }
}
