<?php

namespace Tests\Feature;

use App\Models\StaticPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticPagesAdminTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_disable_and_restore_static_page(): void
    {
        $admin = $this->adminUser();
        $page = StaticPage::query()->create([
            'key' => 'about',
            'is_active' => true,
        ]);

        $disable = $this->actingAs($admin)->delete(route('admin.static-pages.destroy', $page));
        $disable->assertRedirect(route('admin.static-pages.index'));
        $disable->assertSessionHas('success', __('site.admin_static_page_deleted'));
        $this->assertDatabaseHas('static_pages', [
            'id' => $page->id,
            'is_active' => 0,
        ]);

        $restore = $this->actingAs($admin)->patch(route('admin.static-pages.restore', $page));
        $restore->assertRedirect(route('admin.static-pages.index'));
        $restore->assertSessionHas('success', __('site.admin_static_page_restored'));
        $this->assertDatabaseHas('static_pages', [
            'id' => $page->id,
            'is_active' => 1,
        ]);
    }

    public function test_disabled_static_page_returns_404_on_public_site(): void
    {
        StaticPage::query()->create([
            'key' => 'about',
            'is_active' => false,
        ]);

        $this->get('/en/about')->assertNotFound();
    }
}
