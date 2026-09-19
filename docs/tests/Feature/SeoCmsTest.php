<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\SeoRedirect;
use App\Models\StaticPage;
use App\Models\StaticPageTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SeoCmsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function seedCategory(string $locale = 'en'): Category
    {
        $category = Category::query()->create([
            'key' => 'technology',
            'sort_order' => 1,
        ]);

        foreach (config('novaranews.locales', ['en']) as $loc) {
            CategoryTranslation::query()->create([
                'category_id' => $category->id,
                'locale' => $loc,
                'name' => strtoupper($loc).' Technology',
                'slug' => $loc.'-technology',
            ]);
        }

        return $category;
    }

    private function assertSeoFieldsPersisted(Article $article): void
    {
        $this->assertDatabaseHas('article_translations', [
            'article_id' => $article->id,
            'locale' => 'en',
            'og_title' => 'OG title',
            'canonical_url' => url('/en/analysis/new-title'),
            'robots_noindex' => 1,
            'robots_nofollow' => 1,
        ]);
    }

    private function assertPermanentRedirect(TestResponse $response, string $target): void
    {
        $response->assertRedirect($target);
        $response->assertStatus(301);
    }

    public function test_article_update_persists_advanced_seo_fields(): void
    {
        $admin = $this->admin();
        $category = $this->seedCategory();

        $article = Article::query()->create([
            'category_id' => $category->id,
            'user_id' => $admin->id,
            'locale' => 'en',
            'status' => 'draft',
            'content_type' => 'news',
        ]);

        ArticleTranslation::query()->create([
            'article_id' => $article->id,
            'locale' => 'en',
            'title' => 'Old title',
            'slug' => 'old-title',
            'body' => '<p>Old body</p>',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.articles.update', $article), [
            'locale' => 'en',
            'category_id' => $category->id,
            'status' => 'draft',
            'content_type' => 'analysis',
            'translation' => [
                'title' => 'New title',
                'slug' => 'new-title',
                'excerpt' => 'SEO excerpt',
                'body' => '<p>Updated body</p>',
                'meta_title' => 'Meta title',
                'meta_description' => 'Meta description',
                'og_title' => 'OG title',
                'og_description' => 'OG description',
                'canonical_url' => url('/en/news/new-title'),
                'og_image_url' => url('/og.jpg'),
                'robots_noindex' => '1',
                'robots_nofollow' => '1',
                'sources_json' => 'Reuters, AP',
            ],
        ]);

        $response->assertRedirect();
        $this->assertSeoFieldsPersisted($article);
    }

    public function test_legacy_article_url_redirects_to_canonical(): void
    {
        $admin = $this->admin();
        $category = $this->seedCategory();

        $article = Article::query()->create([
            'category_id' => $category->id,
            'user_id' => $admin->id,
            'locale' => 'en',
            'status' => 'published',
            'published_at' => now()->subMinute(),
            'content_type' => 'news',
        ]);

        ArticleTranslation::query()->create([
            'article_id' => $article->id,
            'locale' => 'en',
            'title' => 'Legacy article',
            'slug' => 'legacy-article',
            'body' => '<p>Body</p>',
        ]);

        $response = $this->get('/en/en-technology/legacy-article');
        $this->assertPermanentRedirect($response, '/en/'.article_path_segment('en').'/legacy-article');
    }

    public function test_custom_redirect_rule_applies(): void
    {
        $target = url('/en/news/new-path');
        SeoRedirect::query()->create([
            'locale' => 'en',
            'from_path' => 'old-path',
            'to_url' => $target,
            'status_code' => 301,
            'is_active' => true,
        ]);

        $response = $this->get('/en/old-path');
        $this->assertPermanentRedirect($response, $target);
    }

    public function test_two_segment_legacy_redirect_rule_applies_when_article_match_missing(): void
    {
        $target = url('/en/news/new-path');
        SeoRedirect::query()->create([
            'locale' => 'en',
            'from_path' => 'old-category/old-article',
            'to_url' => $target,
            'status_code' => 301,
            'is_active' => true,
        ]);

        $response = $this->get('/en/old-category/old-article');
        $this->assertPermanentRedirect($response, $target);
    }

    public function test_dynamic_static_page_slug_dispatches_to_controller_with_dependencies(): void
    {
        $page = StaticPage::query()->create([
            'key' => 'about',
            'is_active' => true,
        ]);

        StaticPageTranslation::query()->create([
            'static_page_id' => $page->id,
            'locale' => 'tr',
            'title' => 'Hakkimizda',
            'slug' => 'hakkimizda-biz',
            'content' => '<p>Kurumsal metin</p>',
        ]);

        $response = $this->get('/tr/hakkimizda-biz');
        $response->assertOk();
        $response->assertSee('Hakkimizda');
    }

    public function test_author_page_normalizes_twitter_creator_handle(): void
    {
        $author = User::factory()->create([
            'slug' => 'seo-editor',
            'twitter' => '@novaraeditor',
        ]);

        $response = $this->get(route('author.show', ['locale' => 'en', 'slug' => $author->slug]));
        $response->assertOk();
        $response->assertSee('<meta name="twitter:creator" content="@novaraeditor">', false);
        $response->assertSee('https://x.com/novaraeditor');
    }

    public function test_authors_index_lists_profiled_author_even_without_published_articles(): void
    {
        $author = User::factory()->create([
            'name' => 'Profiled Author',
            'slug' => 'profiled-author',
            'title' => 'Senior Editor',
            'bio' => 'Writes about AI, software, and hardware trends.',
        ]);

        $response = $this->get(route('authors.index', ['locale' => 'en']));

        $response->assertOk();
        $response->assertSee('Profiled Author');
        $response->assertSee($author->profileUrl('en'));
    }

    public function test_sitemap_index_contains_split_entries(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertOk();
        $response->assertSee('/sitemap-en-pages.xml', false);
        $response->assertDontSee('/sitemap-urls.xml', false);
        $response->assertDontSee('/news-sitemap.xml', false);
    }
}
