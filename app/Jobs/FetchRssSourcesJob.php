<?php

namespace App\Jobs;

use App\Services\RssFetcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchRssSourcesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 120;

    public function handle(RssFetcherService $fetcher): void
    {
        $count = $fetcher->fetchAll();
        Log::info("FetchRssSourcesJob: queued {$count} new article(s) for generation.");
    }
}
