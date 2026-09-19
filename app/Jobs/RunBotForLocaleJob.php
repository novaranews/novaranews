<?php

namespace App\Jobs;

use App\Jobs\GenerateArticleJob;
use App\Models\AiArticleGeneration;
use App\Models\Category;
use App\Services\RssFetcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;

class RunBotForLocaleJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        private readonly ?string $locale,
        private readonly int $limit,
        private readonly ?string $categoryKey = null,
    ) {}

    public function handle(RssFetcherService $fetcher): void
    {
        // 1. Fetch RSS (filtered by locale and/or category when specified)
        if ($this->locale && $this->categoryKey) {
            $fetcher->fetchByLocaleAndCategory($this->locale, $this->categoryKey);
        } elseif ($this->locale) {
            $fetcher->fetchByLocale($this->locale);
        } elseif ($this->categoryKey) {
            $fetcher->fetchByCategory($this->categoryKey);
        } else {
            $fetcher->fetchAll();
        }

        // 2. Pick items balanced by language × category, dispatch each
        $ids = $this->locale
            ? $this->pickBalancedByCategory($this->locale)
            : $this->pickBalancedByLocaleAndCategory();

        foreach ($ids as $id) {
            GenerateArticleJob::dispatch($id);
        }

        // Delete pending records that were NOT selected for this run (overflow cleanup).
        // Only when we actually dispatched something — if nothing was picked, do not wipe the queue
        // (e.g. RSS returned no new rows or filters matched nothing).
        if ($ids->isNotEmpty()) {
            AiArticleGeneration::query()
                ->where('status', AiArticleGeneration::STATUS_PENDING)
                ->when($this->locale, fn ($q) => $q->where('source_locale', $this->locale))
                ->when($this->categoryKey, fn ($q) => $q->where('source_packets->category_key', $this->categoryKey))
                ->whereNotIn('id', $ids->all())
                ->delete();
        }
    }

    /**
     * Single language: round-robin across categories (or a specific one if categoryKey is set).
     *
     * limit=8  → 1 per category (world, politics, business, ...)
     * limit=16 → 2 per category
     * limit=5  → first 5 categories get 1 each
     *
     * @return Collection<int>
     */
    private function pickBalancedByCategory(string $locale): Collection
    {
        $categoryKeys = $this->categoryKey
            ? collect([$this->categoryKey])
            : Category::orderedKeys();

        $collected = collect();

        // When a single category is targeted, skip round-robin and fetch directly
        if ($this->categoryKey) {
            AiArticleGeneration::query()
                ->where('status', AiArticleGeneration::STATUS_PENDING)
                ->where('source_locale', $locale)
                ->where('source_packets->category_key', $this->categoryKey)
                ->oldest()
                ->limit($this->limit)
                ->pluck('id')
                ->each(fn ($id) => $collected->push($id));

            return $collected;
        }

        $rounds = (int) ceil($this->limit / max(1, $categoryKeys->count()));

        foreach (range(1, $rounds) as $_) {
            foreach ($categoryKeys as $key) {
                if ($collected->count() >= $this->limit) {
                    break 2;
                }

                $id = AiArticleGeneration::query()
                    ->where('status', AiArticleGeneration::STATUS_PENDING)
                    ->where('source_locale', $locale)
                    ->where('source_packets->category_key', $key)
                    ->whereNotIn('id', $collected->isEmpty() ? [0] : $collected->all())
                    ->oldest()
                    ->value('id');

                if ($id !== null) {
                    $collected->push($id);
                }
            }
        }

        // Fill any remaining slots (e.g. some categories empty)
        if ($collected->count() < $this->limit) {
            AiArticleGeneration::query()
                ->where('status', AiArticleGeneration::STATUS_PENDING)
                ->where('source_locale', $locale)
                ->whereNotIn('id', $collected->isEmpty() ? [0] : $collected->all())
                ->oldest()
                ->limit($this->limit - $collected->count())
                ->pluck('id')
                ->each(fn ($id) => $collected->push($id));
        }

        return $collected;
    }

    /**
     * All languages: round-robin across locale × category combinations.
     * If categoryKey is set, only that category is used.
     *
     * @return Collection<int>
     */
    private function pickBalancedByLocaleAndCategory(): Collection
    {
        $locales   = collect(config('novaranews.locales', ['en']));
        $collected = collect();

        // When a single category is targeted, skip round-robin and fetch directly (round-robin by locale only)
        if ($this->categoryKey) {
            $rounds = (int) ceil($this->limit / max(1, $locales->count()));
            foreach (range(1, $rounds) as $_) {
                foreach ($locales as $locale) {
                    if ($collected->count() >= $this->limit) {
                        break 2;
                    }
                    $id = AiArticleGeneration::query()
                        ->where('status', AiArticleGeneration::STATUS_PENDING)
                        ->where('source_locale', $locale)
                        ->where('source_packets->category_key', $this->categoryKey)
                        ->whereNotIn('id', $collected->isEmpty() ? [0] : $collected->all())
                        ->oldest()
                        ->value('id');
                    if ($id !== null) {
                        $collected->push($id);
                    }
                }
            }
            // Fill remaining from any locale in the same category
            if ($collected->count() < $this->limit) {
                AiArticleGeneration::query()
                    ->where('status', AiArticleGeneration::STATUS_PENDING)
                    ->where('source_packets->category_key', $this->categoryKey)
                    ->whereNotIn('id', $collected->isEmpty() ? [0] : $collected->all())
                    ->oldest()
                    ->limit($this->limit - $collected->count())
                    ->pluck('id')
                    ->each(fn ($id) => $collected->push($id));
            }
            return $collected;
        }

        $categoryKeys  = Category::orderedKeys();
        $slotsPerRound = $categoryKeys->count() * $locales->count();
        $rounds        = (int) ceil($this->limit / max(1, $slotsPerRound));

        foreach (range(1, $rounds) as $_) {
            foreach ($categoryKeys as $key) {
                foreach ($locales as $locale) {
                    if ($collected->count() >= $this->limit) {
                        break 3;
                    }

                    $id = AiArticleGeneration::query()
                        ->where('status', AiArticleGeneration::STATUS_PENDING)
                        ->where('source_locale', $locale)
                        ->where('source_packets->category_key', $key)
                        ->whereNotIn('id', $collected->isEmpty() ? [0] : $collected->all())
                        ->oldest()
                        ->value('id');

                    if ($id !== null) {
                        $collected->push($id);
                    }
                }
            }
        }

        // Fill remaining slots from any locale/category
        if ($collected->count() < $this->limit) {
            AiArticleGeneration::query()
                ->where('status', AiArticleGeneration::STATUS_PENDING)
                ->whereNotIn('id', $collected->isEmpty() ? [0] : $collected->all())
                ->oldest()
                ->limit($this->limit - $collected->count())
                ->pluck('id')
                ->each(fn ($id) => $collected->push($id));
        }

        return $collected;
    }
}
