<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\SubmitController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/live/today.json', [HomeController::class, 'liveToday'])->middleware('throttle:60,1')->name('live.today');

// Anonymous global chat ("Maker lounge")
Route::get('/chat/messages', [\App\Http\Controllers\ChatController::class, 'index'])->middleware('throttle:40,1')->name('chat.index');
Route::post('/chat/messages', [\App\Http\Controllers\ChatController::class, 'store'])->middleware('throttle:20,1')->name('chat.store');
Route::post('/chat/name', [\App\Http\Controllers\ChatController::class, 'rename'])->middleware('throttle:10,1')->name('chat.rename');
Route::get('/rankings/{period?}', [RankingController::class, 'show'])->name('rankings');
Route::get('/category/{category}', [CategoryController::class, 'show'])->name('category');
Route::get('/apps/{product}', [ProductController::class, 'show'])->name('product');
Route::get('/apps/{product}/alternatives', [\App\Http\Controllers\DiscoverController::class, 'alternatives'])->name('alternatives');
Route::get('/discover', [\App\Http\Controllers\DiscoverController::class, 'index'])->name('discover');
Route::get('/@{username}', [\App\Http\Controllers\ProfileController::class, 'show'])->where('username', '[A-Za-z0-9_]{3,30}')->name('profile');
Route::get('/pricing', fn (\App\Services\Pricing $pricing) => view('pricing', ['plans' => config('rankedlaunch.plans'), 'pricing' => $pricing]))->name('pricing');
Route::get('/launch', [\App\Http\Controllers\MakersController::class, 'index'])->name('launch');
Route::view('/how-it-works', 'how-it-works')->name('how-it-works');
Route::post('/apps/{product}/click', [ProductController::class, 'click'])->middleware('throttle:30,1')->name('click');
Route::get('/og.png', [\App\Http\Controllers\ShareImageController::class, 'site'])->name('og.site');
Route::get('/og/{product}.png', [\App\Http\Controllers\ShareImageController::class, 'product'])->name('og.product');
Route::get('/badge/{product}.svg', [BadgeController::class, 'show'])->name('badge');
Route::get('/badge/{product}.png', [BadgeController::class, 'png'])->name('badge.png');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [\App\Http\Controllers\DiscoveryController::class, 'robots']);
Route::get('/llms.txt', [\App\Http\Controllers\DiscoveryController::class, 'llms'])->name('llms');
Route::get('/feed.xml', [\App\Http\Controllers\DiscoveryController::class, 'feed'])->name('feed');
Route::get('/launches.json', [\App\Http\Controllers\DiscoveryController::class, 'json'])->name('launches.json');

// Company and legal pages
Route::view('/about', 'legal.about')->name('about');
Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/refunds', 'legal.refunds')->name('refunds');

// Newsletter
Route::post('/subscribe', [\App\Http\Controllers\SubscribeController::class, 'store'])->middleware('throttle:5,1')->name('subscribe');

// Payments webhook (CSRF-exempt, see bootstrap/app.php)
Route::post('/webhooks/lemonsqueezy', [CheckoutController::class, 'webhook'])->name('webhooks.lemonsqueezy');
Route::post('/webhooks/polar', [CheckoutController::class, 'polarWebhook'])->name('webhooks.polar');

// Guests
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');
});

// Signed-in makers
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/{product}', [DashboardController::class, 'show'])->name('launch.hub');
    Route::delete('/dashboard/{product}', [DashboardController::class, 'destroy'])->name('launch.destroy');
    Route::get('/dashboard/{product}/date', [DashboardController::class, 'reschedule'])->name('launch.date');
    Route::post('/dashboard/{product}/date', [DashboardController::class, 'updateDate'])->middleware('throttle:10,1')->name('launch.reschedule');

    Route::get('/account', [\App\Http\Controllers\AccountController::class, 'edit'])->name('account');
    Route::put('/account', [\App\Http\Controllers\AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [\App\Http\Controllers\AccountController::class, 'password'])->middleware('throttle:6,1')->name('account.password');
    Route::post('/account/timezone', [\App\Http\Controllers\AccountController::class, 'timezone'])->middleware('throttle:10,1')->name('account.timezone');

    Route::post('/apps/{product}/vote', [VoteController::class, 'toggle'])->middleware('throttle:60,1')->name('vote');
    Route::post('/apps/{product}/comments', [\App\Http\Controllers\CommentController::class, 'store'])->middleware('throttle:10,1')->name('comments.store');
    Route::delete('/comments/{comment}', [\App\Http\Controllers\CommentController::class, 'destroy'])->name('comments.destroy');

    Route::get('/submit', [SubmitController::class, 'create'])->name('submit');
    Route::post('/submit', [SubmitController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/submit/{product}/edit', [SubmitController::class, 'edit'])->name('submit.edit');
    Route::put('/submit/{product}', [SubmitController::class, 'update'])->name('submit.update');
    Route::get('/submit/{product}/launch', [SubmitController::class, 'plan'])->name('submit.plan');
    Route::post('/submit/{product}/launch', [SubmitController::class, 'choosePlan']);

    Route::get('/checkout/{payment}/simulate', [CheckoutController::class, 'simulate'])->name('checkout.simulate');
    Route::post('/checkout/{payment}/simulate', [CheckoutController::class, 'simulateComplete']);
});

// Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', \App\Http\Controllers\Admin\OverviewController::class)->name('index');

    Route::get('/products', [\App\Http\Controllers\Admin\ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}/edit', [\App\Http\Controllers\Admin\ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [\App\Http\Controllers\Admin\ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [\App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/{product}/approve', [\App\Http\Controllers\Admin\ProductController::class, 'approve'])->name('approve');
    Route::post('/products/{product}/reject', [\App\Http\Controllers\Admin\ProductController::class, 'reject'])->name('reject');
    Route::post('/products/{product}/feature', [\App\Http\Controllers\Admin\ProductController::class, 'feature'])->name('feature');
    Route::post('/products/{product}/recount', [\App\Http\Controllers\Admin\ProductController::class, 'recount'])->name('products.recount');
    Route::post('/products/{product}/email', [\App\Http\Controllers\Admin\ProductController::class, 'email'])->name('products.email');

    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/ban', [\App\Http\Controllers\Admin\UserController::class, 'ban'])->name('users.ban');
    Route::post('/users/{user}/remove-votes', [\App\Http\Controllers\Admin\UserController::class, 'removeVotes'])->name('users.remove-votes');
    Route::post('/users/{user}/admin', [\App\Http\Controllers\Admin\UserController::class, 'toggleAdmin'])->name('users.admin');
    Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments');
    Route::post('/payments/{payment}/refund', [\App\Http\Controllers\Admin\PaymentController::class, 'refund'])->name('payments.refund');

    Route::get('/votes', \App\Http\Controllers\Admin\VoteController::class)->name('votes');

    Route::get('/subscribers', [\App\Http\Controllers\Admin\SubscriberController::class, 'index'])->name('subscribers.index');
    Route::get('/subscribers.csv', [\App\Http\Controllers\Admin\SubscriberController::class, 'export'])->name('subscribers');
    Route::post('/subscribers/{subscriber}/toggle', [\App\Http\Controllers\Admin\SubscriberController::class, 'toggle'])->name('subscribers.toggle');

    Route::get('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories');
    Route::post('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category:id}', [\App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category:id}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/activity', \App\Http\Controllers\Admin\ActivityController::class)->name('activity');

    Route::get('/chat', [\App\Http\Controllers\Admin\ChatController::class, 'index'])->name('chat');
    Route::delete('/chat/{message}', [\App\Http\Controllers\Admin\ChatController::class, 'destroy'])->name('chat.destroy');
    Route::post('/chat/{message}/mute', [\App\Http\Controllers\Admin\ChatController::class, 'mute'])->name('chat.mute');
    Route::post('/chat/{message}/ban', [\App\Http\Controllers\Admin\ChatController::class, 'ban'])->name('chat.ban');
    Route::post('/chat/mutes/{mute}/unmute', [\App\Http\Controllers\Admin\ChatController::class, 'unmute'])->name('chat.unmute');
    Route::post('/chat/clear', [\App\Http\Controllers\Admin\ChatController::class, 'clear'])->name('chat.clear');

    Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');

    Route::get('/system', [\App\Http\Controllers\Admin\SystemController::class, 'index'])->name('system');
    Route::post('/system/run-scheduler', [\App\Http\Controllers\Admin\SystemController::class, 'runScheduler'])->name('system.run');
    Route::post('/system/test-email', [\App\Http\Controllers\Admin\SystemController::class, 'testEmail'])->name('system.email');
    Route::post('/system/clear-cache', [\App\Http\Controllers\Admin\SystemController::class, 'clearCache'])->name('system.cache');
});

// Local only: preview launch emails in the browser, e.g. /dev/emails/results
if (app()->environment('local')) {
    Route::get('/dev/emails/{type}', function (string $type) {
        abort_unless(in_array($type, \App\Mail\LaunchEmail::TYPES), 404);
        $product = \App\Models\Product::live()->ranked()->firstOrFail();

        return new \App\Mail\LaunchEmail($product, $type);
    });
}
