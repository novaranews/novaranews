<?php

namespace App\Observers;

use App\Models\Article;
use App\Support\SiteCache;

/**
 * Ensures homepage / nav / breaking caches refresh when articles change outside
 * the few admin actions that already call SiteCache::forgetHomeAndNav().
 */
class ArticleObserver
{
    public function saved(Article $article): void
    {
        SiteCache::forgetHomeAndNav();
    }

    public function deleted(Article $article): void
    {
        SiteCache::forgetHomeAndNav();
    }
}
