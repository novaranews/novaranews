<?php

namespace Tests\Feature;

use App\Jobs\GenerateArticleJob;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\NewsSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminFlowsTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_settings_update_uses_translated_flash_message(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'group' => 'bot',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', __('site.admin_settings_saved'));
    }

    public function test_dashboard_shows_google_news_readiness_widget(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(__('site.admin_readiness_title'));
        $response->assertSee(__('site.admin_readiness_fix'));
    }

    public function test_admin_bot_manual_generate_queues_job_with_translated_message(): void
    {
        Queue::fake();
        Http::fake([
            '*' => Http::response('<html><head><title>Source</title></head><body><p>Sample extracted source text for tests.</p></body></html>', 200),
        ]);
        $admin = $this->adminUser();

        $category = Category::query()->create([
            'key' => 'technology',
            'sort_order' => 1,
        ]);
        CategoryTranslation::query()->create([
            'category_id' => $category->id,
            'locale' => 'en',
            'name' => 'Technology',
            'slug' => 'technology',
        ]);

        $categoryKey = 'technology';
        $categoryLabel = 'Technology';

        $payload = [
            'topic' => 'Open source AI model benchmark roundup',
            'source_url' => 'https://example.com/source-article',
            'context' => 'Weekly analysis draft',
            'category_key' => $categoryKey,
            'locale' => 'en',
        ];

        $response = $this->actingAs($admin)->post(route('admin.ai-generations.manual'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success', __('site.admin_manual_generation_queued', [
            'category' => $categoryLabel,
            'topic' => $payload['topic'],
        ]));

        Queue::assertPushed(GenerateArticleJob::class);
        $this->assertDatabaseHas('ai_article_generations', [
            'source_title' => $payload['topic'],
            'source_locale' => 'en',
            'status' => 'pending',
        ]);
    }

    public function test_admin_source_bulk_destroy_uses_translated_messages(): void
    {
        $admin = $this->adminUser();

        $errorResponse = $this->actingAs($admin)->delete(route('admin.ai-generations.source-bulk-destroy'), [
            'ids' => [],
        ]);
        $errorResponse->assertSessionHas('errors');

        $first = NewsSource::query()->create([
            'name' => 'TechSource 1',
            'url' => 'https://example.com/feed-1.xml',
            'locale' => 'en',
            'category_key' => 'technology',
            'is_active' => true,
        ]);
        $second = NewsSource::query()->create([
            'name' => 'TechSource 2',
            'url' => 'https://example.com/feed-2.xml',
            'locale' => 'en',
            'category_key' => 'technology',
            'is_active' => true,
        ]);

        $okResponse = $this->actingAs($admin)->delete(route('admin.ai-generations.source-bulk-destroy'), [
            'ids' => [$first->id, $second->id],
        ]);

        $okResponse->assertRedirect(route('admin.ai-generations.sources'));
        $okResponse->assertSessionHas('success', __('site.admin_sources_deleted', ['count' => 2]));
        $this->assertDatabaseMissing('news_sources', ['id' => $first->id]);
        $this->assertDatabaseMissing('news_sources', ['id' => $second->id]);
    }

    public function test_admin_category_update_persists_all_locale_translations(): void
    {
        $admin = $this->adminUser();

        $category = Category::query()->create([
            'key' => 'technology',
            'sort_order' => 1,
        ]);

        foreach (config('novaranews.locales', ['en']) as $locale) {
            CategoryTranslation::query()->create([
                'category_id' => $category->id,
                'locale' => $locale,
                'name' => strtoupper($locale).' Old Name',
                'slug' => $locale.'-old-slug',
            ]);
        }

        $translations = [];
        foreach (config('novaranews.locales', ['en']) as $locale) {
            $translations[$locale] = [
                'name' => strtoupper($locale).' Updated Name',
                'slug' => $locale.'-updated-slug',
            ];
        }

        $response = $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'sort_order' => 7,
            'translations' => $translations,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success', __('Category updated.'));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'sort_order' => 7,
        ]);
        $this->assertDatabaseHas('category_translations', [
            'category_id' => $category->id,
            'locale' => 'en',
            'slug' => 'en-updated-slug',
        ]);
    }
}
