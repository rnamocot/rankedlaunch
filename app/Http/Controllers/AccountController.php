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
        ]);

        // Placeholder emails from X sign-in can always be replaced with a real one.
        if ($data['email'] !== $user->email) {
            $user->email_verified_at = null;
        }

        $user->fill($data)->save();

        return back()->with('status', 'Profile saved.');
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
