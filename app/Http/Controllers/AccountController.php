<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function edit(Request $request)
    {
        return view('account', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'username' => ['sometimes', 'required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_]+$/', Rule::notIn(User::RESERVED_USERNAMES), Rule::unique('users')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:160'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'x_handle' => ['nullable', 'string', 'max:30', 'regex:/^@?[A-Za-z0-9_]{1,15}$/'],
        ], ['username.regex' => 'Use lowercase letters, numbers and underscores only.']);
        $data['x_handle'] = isset($data['x_handle']) ? ltrim($data['x_handle'], '@') : null;

        // Placeholder emails from X sign-in can always be replaced with a real one.
        if ($data['email'] !== $user->email) {
            $user->email_verified_at = null;
        }

        $user->fill($data)->save();

        return back()->with('status', 'Profile saved.');
    }

    /** Called quietly by the browser so emails can show launch times in the maker's own time zone. */
    public function timezone(Request $request)
    {
        $tz = (string) $request->input('timezone');
        abort_unless(in_array($tz, timezone_identifiers_list(), true), 422);

        $request->user()->forceFill(['timezone' => $tz])->save();

        return response()->noContent();
    }

    public function password(Request $request)
    {
        $user = $request->user();

        $request->validate([
            // Accounts created with Google/GitHub/X have no password yet: let them set one.
            'current_password' => [$user->password ? 'required' : 'nullable', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($user->password && ! Hash::check((string) $request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'That’s not your current password.']);
        }

        $hadPassword = (bool) $user->password;
        $user->forceFill(['password' => $request->input('password')])->save();

        return back()->with('status', $hadPassword ? 'Password updated.' : 'Password set. You can now also sign in with your email.');
    }
}
