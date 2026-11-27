<?php

namespace App\Providers;

use App\Http\Controllers\SocialAuthController;
use App\Models\Vote;
use App\Services\Pricing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Admin-edited prices, offer and queue limits override config/rankedlaunch.php.
        app(\App\Services\Settings::class)->apply();

        // Product IDs the signed-in user has upvoted, resolved once per request.
        View::composer('*', function ($view) {
            $attributes = request()->attributes;

            if (! $attributes->has('voted_ids')) {
                $attributes->set('voted_ids', Auth::check()
                    ? Vote::where('user_id', Auth::id())->pluck('product_id')->flip()->all()
                    : []);
            }

            $view->with('votedIds', $attributes->get('voted_ids'));
        });

        View::composer('*', fn ($view) => $view->with('pricing', app(Pricing::class)));

        View::share('socialProviders', SocialAuthController::PROVIDERS);
    }
}
