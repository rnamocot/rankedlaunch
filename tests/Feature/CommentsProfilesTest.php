<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CommentsProfilesTest extends TestCase
{
    use RefreshDatabase;

    private function product(User $maker, array $attrs = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'ai'], ['name' => 'AI']);

        return Product::create($attrs + [
            'user_id' => $maker->id, 'category_id' => $cat->id, 'name' => 'Acme', 'slug' => 'acme', 'url' => 'https://a.test',
            'tagline' => 't', 'description' => str_repeat('x', 50), 'status' => 'approved', 'launch_date' => today()->subDays(2), 'votes_count' => 3,
        ]);
    }

    public function test_signed_in_users_can_comment_and_makers_reply_with_a_label(): void
    {
        $maker = User::factory()->create(['name' => 'Renie Maker']);
        $fan = User::factory()->create(['name' => 'Fan User']);
        $p = $this->product($maker);

        $this->post('/apps/acme/comments', ['body' => 'nice'])->assertRedirect('/login');

        $this->actingAs($fan)->post('/apps/acme/comments', ['body' => 'Love the onboarding!'])->assertRedirect();
        $parent = Comment::first();
        Cache::flush();
        $this->actingAs($maker)->post('/apps/acme/comments', ['body' => 'Thank you!', 'parent_id' => $parent->id])->assertRedirect();

        $this->assertSame($parent->id, Comment::latest('id')->first()->parent_id);
        $this->get('/apps/acme')->assertOk()->assertSee('Love the onboarding!')->assertSee('Thank you!')->assertSee('Maker')->assertSee('Comments');
    }

    public function test_comment_filters_and_slow_mode(): void
    {
        $user = User::factory()->create();
        $this->product(User::factory()->create());

        $this->actingAs($user)->post('/apps/acme/comments', ['body' => 'visit spam.example.com'])->assertSessionHasErrors('body');
        $this->actingAs($user)->post('/apps/acme/comments', ['body' => 'you idiot'])->assertSessionHasErrors('body');
        $this->actingAs($user)->post('/apps/acme/comments', ['body' => 'First helpful comment'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post('/apps/acme/comments', ['body' => 'Second one right away'])->assertSessionHasErrors('body');
        $this->assertSame(1, Comment::count());
    }

    public function test_author_or_admin_can_delete_others_cannot(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $p = $this->product(User::factory()->create());
        $c1 = Comment::create(['product_id' => $p->id, 'user_id' => $author->id, 'body' => 'one']);
        $c2 = Comment::create(['product_id' => $p->id, 'user_id' => $author->id, 'body' => 'two']);

        $this->actingAs($other)->delete("/comments/{$c1->id}")->assertForbidden();
        $this->actingAs($author)->delete("/comments/{$c1->id}")->assertRedirect();
        $this->actingAs($admin)->delete("/comments/{$c2->id}")->assertRedirect();
        $this->assertSame(0, Comment::count());
    }

    public function test_profiles_have_usernames_launches_and_settings(): void
    {
        $maker = User::factory()->create(['name' => 'Renie Namocot']);
        $this->assertSame('renienamocot', $maker->username);
        $this->product($maker);

        $this->get('/@renienamocot')->assertOk()->assertSee('Renie Namocot')->assertSee('Acme')->assertSee('"@type":"ProfilePage"', false);
        $this->get('/@nobody_here')->assertNotFound();
        $this->get('/apps/acme')->assertSee('/@renienamocot', false);

        $this->actingAs($maker)->put('/account', [
            'name' => 'Renie', 'email' => $maker->email, 'username' => 'renie', 'bio' => 'I build small useful apps',
            'website' => 'https://renienamocot.com', 'x_handle' => '@Zendzdev',
        ])->assertSessionHasNoErrors();
        $this->assertSame(['renie', 'Zendzdev'], [$maker->fresh()->username, $maker->fresh()->x_handle]);
        $this->get('/@renie')->assertOk()->assertSee('I build small useful apps');

        $this->actingAs($maker)->put('/account', ['name' => 'R', 'email' => $maker->email, 'username' => 'admin'])->assertSessionHasErrors('username');
    }
}
