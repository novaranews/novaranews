<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_default_locale(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/en');
    }

    public function test_public_home_returns_ok(): void
    {
        $response = $this->get('/en');

        $response->assertOk();
    }
}
