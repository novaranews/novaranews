<?php

namespace App\Jobs;

use App\Models\AiArticleGeneration;
use App\Services\ClaudeArticleGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;

class GenerateArticleJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 300; // tool-use loop (fetch_url + multi-round Claude) needs more time
    public int $backoff = 60;  // 60s between retries — gives Claude API rate limit time to recover

    public function __construct(private readonly int $generationId) {}

    /**
     * Prevent duplicate jobs for the same generation record at queue level.
     * ThrottlesExceptions: on rate-limit (429) errors, pause the whole job queue
     * for 90 seconds before releasing this job back so it retries cleanly.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->generationId, releaseAfter: 60),
            (new ThrottlesExceptions(3, 90))->by('claude-api'),
        ];
    }

    public function handle(ClaudeArticleGeneratorService $generator): void
    {
        // Atomic claim: only one job can flip pending → processing.
        // Uses a DB-level WHERE clause so concurrent workers cannot both proceed.
        $claimed = DB::table('ai_article_generations')
            ->where('id', $this->generationId)
            ->where('status', AiArticleGeneration::STATUS_PENDING)
            ->update(['status' => AiArticleGeneration::STATUS_PROCESSING]);

        if ($claimed === 0) {
            // Another job already claimed this record (or it doesn't exist).
            return;
        }

        $gen = AiArticleGeneration::query()->find($this->generationId);

        if (! $gen) {
            return;
        }

        // Throttle: 30k input tokens/min limit. Each article with tool-use rounds
        // consumes ~20-25k tokens. Wait 45s between jobs to stay safely under limit.
        sleep(45);

        // Pass already-processing record to service (status already set above).
        $generator->processAlreadyClaimed($gen);
    }
}
