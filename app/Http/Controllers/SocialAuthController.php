<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    public const PROVIDERS = ['google', 'github', 'x'];

    public static function enabled(): array
    {
        return array_values(array_filter(self::PROVIDERS, fn ($p) => filled(config("services.$p.client_id"))));
    }

    public function redirect(string $provider)
    {
        abort_unless(in_array($provider, self::PROVIDERS), 404);

        if (! in_array($provider, self::enabled())) {
            return redirect()->route('login')->withErrors(['email' => ucfirst($provider).' sign-in is not set up yet. Add its keys to .env.']);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider)
    {
        abort_unless(in_array($provider, self::enabled()), 404);

        try {
            $social = Socialite::driver($provider)->user();
        } catch (Throwable) {
            return redirect()->route('login')->withErrors(['email' => 'Sign in was cancelled or failed. Please try again.']);
        }

        // X does not always return an email; fall back to a stable placeholder.
        $email = $social->getEmail() ?: "{$provider}_{$social->getId()}".User::PLACEHOLDER_DOMAIN;

        $user = User::where('provider', $provider)->where('provider_id', $social->getId())->first()
            ?? User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $social->getName() ?: $social->getNickname() ?: 'Maker',
                'email' => $email,
            ]);
            $user->email_verified_at = now();
            $isNew = true;
        }

        $user->provider = $provider;
        $user->provider_id = $social->getId();
        $user->avatar = $social->getAvatar();
        $user->save();

        if ($isNew ?? false) {
            \App\Models\ActivityLog::record('signup', "{$user->name} signed up with ".ucfirst($provider), user: $user);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
