<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirect_prefers_site_locale_cookie(): void
    {
        $response = $this->withCookie('site_locale', 'tr')
            ->withHeader('Accept-Language', 'de-DE,de;q=0.9,en;q=0.8')
            ->get('/');

        $response->assertRedirect('/tr');
        $response->assertStatus(302);
    }

    public function test_root_redirect_uses_browser_locale_when_cookie_missing(): void
    {
        $response = $this->withHeader('Accept-Language', 'de-DE,de;q=0.9,en;q=0.8')->get('/');

        $response->assertRedirect('/de');
        $response->assertStatus(302);
    }

    public function test_admin_locale_query_persists_to_database_and_session(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'admin_locale' => 'en',
        ]);

        $response = $this->actingAs($admin)->get('/admin/settings?admin_locale=tr');

        $response->assertOk();
        $response->assertSessionHas('admin_locale', 'tr');
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'admin_locale' => 'tr',
        ]);
    }

    public function test_admin_list_locale_filter_does_not_override_admin_locale(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'admin_locale' => 'tr',
        ]);

        $this->actingAs($admin)->get('/admin/articles?locale=en')->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'admin_locale' => 'tr',
        ]);
    }
}
