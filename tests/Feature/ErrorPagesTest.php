<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Routes that throw each error, so every page can be checked.
        Route::middleware('web')->group(function () {
            Route::get('/_test/403', fn () => abort(403));
            Route::get('/_test/419', fn () => abort(419));
            Route::get('/_test/422', fn () => abort(422, 'Custom reason shown to the user.'));
            Route::get('/_test/429', fn () => abort(429));
            Route::get('/_test/500', fn () => throw new \RuntimeException('boom'));
            Route::get('/_test/503', fn () => abort(503));
        });
    }

    public function test_404_has_search_and_links(): void
    {
        $this->get('/this/does/not/exist')->assertNotFound()
            ->assertSee('This page didn')->assertSee('name="q"', false)->assertSee('Top this week');
    }

    public function test_client_error_pages_use_the_site_layout(): void
    {
        $this->get('/_test/403')->assertForbidden()->assertSee('403')->assertSee('RankedLaunch');
        $this->get('/_test/419')->assertStatus(419)->assertSee('This page expired.');
        $this->get('/_test/422')->assertStatus(422)->assertSee('Custom reason shown to the user.');
        $this->get('/_test/429')->assertStatus(429)->assertSee('Slow down a little.');
    }

    public function test_server_error_pages_are_standalone(): void
    {
        config(['app.debug' => false]);

        $this->get('/_test/500')->assertStatus(500)->assertSee('Something broke on our side.')->assertDontSee('boom');
        $this->get('/_test/503')->assertStatus(503)->assertSee('Back in a moment.');
    }

    public function test_non_admin_sees_404_not_admin_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertNotFound()->assertSee('This page didn');
    }
}
