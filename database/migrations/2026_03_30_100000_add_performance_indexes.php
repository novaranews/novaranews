<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Adds missing indexes for frequently queried columns.
 * Covers: articles status/published_at, article_translations locale+slug,
 *         category_translations locale+slug, contact_messages read state,
 *         ai_article_generations status+created_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        // articles — status + published_at sık beraber kullanılıyor (published scope)
        Schema::table('articles', function (Blueprint $table) {
            if (! $this->hasIndex('articles', 'articles_status_published_at_index')) {
                $table->index(['status', 'published_at'], 'articles_status_published_at_index');
            }
            if (! $this->hasIndex('articles', 'articles_locale_status_index')) {
                $table->index(['locale', 'status'], 'articles_locale_status_index');
            }
        });

        // article_translations — slug lookup ve locale filtresi
        Schema::table('article_translations', function (Blueprint $table) {
            if (! $this->hasIndex('article_translations', 'article_translations_locale_slug_index')) {
                $table->index(['locale', 'slug'], 'article_translations_locale_slug_index');
            }
        });

        // category_translations — locale + slug lookup
        Schema::table('category_translations', function (Blueprint $table) {
            if (! $this->hasIndex('category_translations', 'category_translations_locale_slug_index')) {
                $table->index(['locale', 'slug'], 'category_translations_locale_slug_index');
            }
        });

        // contact_messages — admin panelinde read_at filtrelemesi
        Schema::table('contact_messages', function (Blueprint $table) {
            if (Schema::hasColumn('contact_messages', 'read_at') &&
                ! $this->hasIndex('contact_messages', 'contact_messages_read_at_index')) {
                $table->index('read_at', 'contact_messages_read_at_index');
            }
        });

        // ai_article_generations — status + created_at panelde çok kullanılıyor
        Schema::table('ai_article_generations', function (Blueprint $table) {
            if (! $this->hasIndex('ai_article_generations', 'ai_generations_status_created_at_index')) {
                $table->index(['status', 'created_at'], 'ai_generations_status_created_at_index');
            }
        });

        // news_sources — locale + is_active bot sorgularında kullanılıyor
        Schema::table('news_sources', function (Blueprint $table) {
            if (! $this->hasIndex('news_sources', 'news_sources_locale_active_index')) {
                $table->index(['locale', 'is_active'], 'news_sources_locale_active_index');
            }
            if (! $this->hasIndex('news_sources', 'news_sources_category_active_index')) {
                $table->index(['category_key', 'is_active'], 'news_sources_category_active_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndexIfExists('articles_status_published_at_index');
            $table->dropIndexIfExists('articles_locale_status_index');
        });
        Schema::table('article_translations', function (Blueprint $table) {
            $table->dropIndexIfExists('article_translations_locale_slug_index');
        });
        Schema::table('category_translations', function (Blueprint $table) {
            $table->dropIndexIfExists('category_translations_locale_slug_index');
        });
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndexIfExists('contact_messages_read_at_index');
        });
        Schema::table('ai_article_generations', function (Blueprint $table) {
            $table->dropIndexIfExists('ai_generations_status_created_at_index');
        });
        Schema::table('news_sources', function (Blueprint $table) {
            $table->dropIndexIfExists('news_sources_locale_active_index');
            $table->dropIndexIfExists('news_sources_category_active_index');
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite') {
                $indexes = DB::select("PRAGMA index_list('{$table}')");
                foreach ($indexes as $index) {
                    $name = is_object($index) ? ($index->name ?? null) : ($index['name'] ?? null);
                    if ($name === $indexName) {
                        return true;
                    }
                }
                return false;
            }

            $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        } catch (\Throwable) {
            return false;
        }
    }
};
