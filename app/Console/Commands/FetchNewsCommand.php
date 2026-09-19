<?php

namespace App\Console\Commands;

use App\Jobs\GenerateArticleJob;
use App\Models\AiArticleGeneration;
use App\Services\RssFetcherService;
use Illuminate\Console\Command;

class FetchNewsCommand extends Command
{
    protected $signature = 'news:fetch
                            {--dispatch : Also dispatch GenerateArticleJob for each new pending item}
                            {--source= : Fetch only a specific news_source ID}';

    protected $description = 'Fetch RSS feeds and queue new articles for AI generation';

    public function handle(RssFetcherService $fetcher): int
    {
        $this->info('Fetching RSS sources...');

        $sourceId = $this->option('source');

        if ($sourceId) {
            $source = \App\Models\NewsSource::query()->find((int) $sourceId);
            if (! $source) {
                $this->error("News source #{$sourceId} not found.");
                return self::FAILURE;
            }
            $count = $fetcher->fetchSource($source);
        } else {
            $count = $fetcher->fetchAll();
        }

        $this->info("Queued {$count} new item(s) for generation.");

        if ($this->option('dispatch') && $count > 0) {
            $pending = AiArticleGeneration::query()
                ->where('status', AiArticleGeneration::STATUS_PENDING)
                ->latest()
                ->limit($count)
                ->pluck('id');

            foreach ($pending as $id) {
                GenerateArticleJob::dispatch($id);
            }

            $this->info("Dispatched {$pending->count()} GenerateArticleJob(s).");
        }

        return self::SUCCESS;
    }
}
