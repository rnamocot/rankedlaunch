<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SubscriberController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MakersController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShareImageController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\SubmitController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\UpcomingController;
use App\Http\Controllers\VoteController;
use App\Mail\LaunchEmail;
use App\Models\Product;
use App\Services\Pricing;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/live/today.json', [HomeController::class, 'liveToday'])->middleware('throttle:60,1')->name('live.today');

// Anonymous global chat ("Maker lounge")
Route::get('/chat/messages', [ChatController::class, 'index'])->middleware('throttle:40,1')->name('chat.index');
Route::post('/chat/messages', [ChatController::class, 'store'])->middleware('throttle:20,1')->name('chat.store');
Route::post('/chat/name', [ChatController::class, 'rename'])->middleware('throttle:10,1')->name('chat.rename');
Route::post('/presence', [PresenceController::class, 'ping'])->middleware('throttle:12,1')->name('presence');
Route::get('/live/activity.json', [PresenceController::class, 'activity'])->middleware('throttle:30,1')->name('live.activity');
Route::get('/upcoming', [UpcomingController::class, 'index'])->name('upcoming');
Route::get('/makers/{period?}', [LeaderboardController::class, 'show'])->name('leaderboard');
Route::get('/search.json', SearchController::class)->middleware('throttle:60,1')->name('search');
Route::get('/days/{date}', [HomeController::class, 'day'])->where('date', '\d{4}-\d{2}-\d{2}')->middleware('throttle:60,1')->name('home.day');
Route::get('/rankings/{period?}', [RankingController::class, 'show'])->name('rankings');
Route::get('/category/{category}', [CategoryController::class, 'show'])->name('category');
Route::get('/apps/{product}', [ProductController::class, 'show'])->name('product');
Route::get('/apps/{product}/alternatives', [DiscoverController::class, 'alternatives'])->name('alternatives');
Route::get('/discover', [DiscoverController::class, 'index'])->name('discover');
Route::get('/@{username}', [ProfileController::class, 'show'])->where('username', '[A-Za-z0-9_]{3,30}')->name('profile');
Route::get('/pricing', fn (Pricing $pricing) => view('pricing', ['plans' => config('rankedlaunch.plans'), 'pricing' => $pricing]))->name('pricing');
Route::get('/launch', [MakersController::class, 'index'])->name('launch');
Route::view('/how-it-works', 'how-it-works')->name('how-it-works');
Route::post('/apps/{product}/click', [ProductController::class, 'click'])->middleware('throttle:30,1')->name('click');
Route::get('/og.png', [ShareImageController::class, 'site'])->name('og.site');
Route::get('/og/{product}.png', [ShareImageController::class, 'product'])->name('og.product');
Route::get('/badge/{product}.svg', [BadgeController::class, 'show'])->name('badge');
Route::get('/badge/{product}.png', [BadgeController::class, 'png'])->name('badge.png');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [DiscoveryController::class, 'robots']);
Route::get('/llms.txt', [DiscoveryController::class, 'llms'])->name('llms');
Route::get('/feed.xml', [DiscoveryController::class, 'feed'])->name('feed');
Route::get('/launches.json', [DiscoveryController::class, 'json'])->name('launches.json');

// Company and legal pages
Route::view('/about', 'legal.about')->name('about');
Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/refunds', 'legal.refunds')->name('refunds');

// Newsletter
Route::post('/subscribe', [SubscribeController::class, 'store'])->middleware('throttle:5,1')->name('subscribe');

// Payments webhook (CSRF-exempt, see bootstrap/app.php)
Route::post('/webhooks/lemonsqueezy', [CheckoutController::class, 'webhook'])->name('webhooks.lemonsqueezy');
Route::post('/webhooks/polar', [CheckoutController::class, 'polarWebhook'])->name('webhooks.polar');

// Guests
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
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

    Route::get('/account', [AccountController::class, 'edit'])->name('account');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [AccountController::class, 'password'])->middleware('throttle:6,1')->name('account.password');
    Route::post('/account/timezone', [AccountController::class, 'timezone'])->middleware('throttle:10,1')->name('account.timezone');

    Route::post('/apps/{product}/vote', [VoteController::class, 'toggle'])->middleware('throttle:60,1')->name('vote');
    Route::post('/apps/{product}/comments', [CommentController::class, 'store'])->middleware('throttle:10,1')->name('comments.store');
    Route::post('/comments/{comment}/vote', [EngagementController::class, 'voteComment'])->middleware('throttle:60,1')->name('comments.vote');
    Route::post('/apps/{product}/bookmark', [EngagementController::class, 'bookmark'])->middleware('throttle:60,1')->name('bookmark');
    Route::post('/apps/{product}/remind', [EngagementController::class, 'remind'])->middleware('throttle:60,1')->name('remind');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

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
    Route::get('/', OverviewController::class)->name('index');

    Route::get('/products', [App\Http\Controllers\Admin\ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}/edit', [App\Http\Controllers\Admin\ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [App\Http\Controllers\Admin\ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/{product}/approve', [App\Http\Controllers\Admin\ProductController::class, 'approve'])->name('approve');
    Route::post('/products/{product}/reject', [App\Http\Controllers\Admin\ProductController::class, 'reject'])->name('reject');
    Route::post('/products/{product}/feature', [App\Http\Controllers\Admin\ProductController::class, 'feature'])->name('feature');
    Route::post('/products/{product}/recount', [App\Http\Controllers\Admin\ProductController::class, 'recount'])->name('products.recount');
    Route::post('/products/{product}/email', [App\Http\Controllers\Admin\ProductController::class, 'email'])->name('products.email');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/ban', [UserController::class, 'ban'])->name('users.ban');
    Route::post('/users/{user}/remove-votes', [UserController::class, 'removeVotes'])->name('users.remove-votes');
    Route::post('/users/{user}/admin', [UserController::class, 'toggleAdmin'])->name('users.admin');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments');
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');

    Route::get('/votes', App\Http\Controllers\Admin\VoteController::class)->name('votes');

    Route::get('/subscribers', [SubscriberController::class, 'index'])->name('subscribers.index');
    Route::get('/subscribers.csv', [SubscriberController::class, 'export'])->name('subscribers');
    Route::post('/subscribers/{subscriber}/toggle', [SubscriberController::class, 'toggle'])->name('subscribers.toggle');

    Route::get('/categories', [App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories');
    Route::post('/categories', [App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category:id}', [App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category:id}', [App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/activity', ActivityController::class)->name('activity');

    Route::get('/chat', [App\Http\Controllers\Admin\ChatController::class, 'index'])->name('chat');
    Route::delete('/chat/{message}', [App\Http\Controllers\Admin\ChatController::class, 'destroy'])->name('chat.destroy');
    Route::post('/chat/{message}/mute', [App\Http\Controllers\Admin\ChatController::class, 'mute'])->name('chat.mute');
    Route::post('/chat/{message}/ban', [App\Http\Controllers\Admin\ChatController::class, 'ban'])->name('chat.ban');
    Route::post('/chat/mutes/{mute}/unmute', [App\Http\Controllers\Admin\ChatController::class, 'unmute'])->name('chat.unmute');
    Route::post('/chat/clear', [App\Http\Controllers\Admin\ChatController::class, 'clear'])->name('chat.clear');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('/system', [SystemController::class, 'index'])->name('system');
    Route::post('/system/run-scheduler', [SystemController::class, 'runScheduler'])->name('system.run');
    Route::post('/system/test-email', [SystemController::class, 'testEmail'])->name('system.email');
    Route::post('/system/clear-cache', [SystemController::class, 'clearCache'])->name('system.cache');
});

// Local only: preview launch emails in the browser, e.g. /dev/emails/results
if (app()->environment('local')) {
    Route::get('/dev/emails/{type}', function (string $type) {
        abort_unless(in_array($type, LaunchEmail::TYPES), 404);
        $product = Product::live()->ranked()->firstOrFail();

        return new LaunchEmail($product, $type);
    });
}
