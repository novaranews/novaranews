<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_renders(): void
    {
        $response = $this->get('/en/contact');

        $response->assertOk();
    }

    public function test_contact_form_accepts_valid_submission(): void
    {
        $response = $this->post('/en/contact', [
            'name' => 'Test User',
            'email' => 'reader@example.com',
            'message' => 'Hello from the test suite.',
        ]);

        $response->assertRedirect(route('page.contact', ['locale' => 'en']));
        $response->assertSessionHas('contact_status', 'sent');
    }
}
