<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialButtonsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_configured_providers_show_outside_local(): void
    {
        config(['services.google.client_id' => 'abc', 'services.github.client_id' => null, 'services.x.client_id' => null]);

        $html = $this->get('/login')->assertOk()->getContent();
        $this->assertStringContainsString('Continue with Google', $html);
        $this->assertStringNotContainsString('Continue with GitHub', $html);
        $this->assertStringNotContainsString('Continue with X', $html);
    }

    public function test_unconfigured_provider_link_explains_without_leaking_internals(): void
    {
        config(['services.github.client_id' => null]);

        $this->get('/auth/github/redirect')->assertRedirect('/login');
        $this->followingRedirects()->get('/auth/github/redirect')
            ->assertSee('sign-in isn’t available yet', false)
            ->assertDontSee('Add its keys');
    }
}
