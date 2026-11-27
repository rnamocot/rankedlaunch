<x-layout title="Account settings" :noindex="true">
    @php($placeholder = ! $user->deliverableEmail())
    <div class="mx-auto max-w-2xl px-4 pt-8 sm:px-6">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-ink">
            <x-icon name="chevron-left" :size="16" :stroke="2" /> Dashboard
        </a>
        <div class="intro mt-6 flex items-center gap-4">
            @if ($user->avatar)
                <img src="{{ $user->avatar }}" alt="" class="size-14 rounded-full outline outline-1 -outline-offset-1 outline-line">
            @else
                <span class="grid size-14 place-items-center rounded-full bg-fill text-lg font-semibold">{{ $user->initials() }}</span>
            @endif
            <div>
                <h1 class="text-[1.75rem] font-bold leading-tight tracking-[-0.03em]">Account settings</h1>
                <p class="text-sm text-ink-2">Signed in with {{ $user->provider ? ucfirst($user->provider) : 'email' }} · member since {{ $user->created_at->format('M Y') }}</p>
            </div>
        </div>

        <section class="mt-10">
            <h2 class="group-header">Profile</h2>
            <form method="POST" action="{{ route('account.update') }}" class="card space-y-5 p-5">
                @csrf @method('PUT')
                <div>
                    <label for="name" class="field-label">Name</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="input">
                    <p class="field-hint">Shown as the maker on your product pages.</p>
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="field-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $placeholder ? '' : $user->email) }}" required class="input" @if($placeholder) placeholder="Add your email to get launch emails and receipts" @endif>
                    @if ($placeholder)
                        <p class="field-hint">X didn’t share your email. Add one so we can send your launch reminders and results.</p>
                    @endif
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="username" class="field-label">Username</label>
                    <div class="flex items-center rounded-xl bg-fill pl-3.5 text-sm text-ink-2">
                        <span class="shrink-0">rankedlaunch.com/@</span>
                        <input id="username" name="username" value="{{ old('username', $user->username) }}" required class="input !bg-transparent !pl-0.5" autocomplete="off">
                    </div>
                    @error('username')<p class="field-error">{{ $message }}</p>@else<p class="field-hint">Your public maker profile: <a href="{{ $user->profileUrl() }}" class="font-medium text-ink underline underline-offset-2">view it</a>.</p>@enderror
                </div>
                <div>
                    <label for="bio" class="field-label">Short bio <span class="font-normal text-ink-3">optional</span></label>
                    <input id="bio" name="bio" maxlength="160" value="{{ old('bio', $user->bio) }}" class="input" placeholder="What you build, in one line">
                    @error('bio')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="website" class="field-label">Website <span class="font-normal text-ink-3">optional</span></label>
                        <input id="website" name="website" type="url" value="{{ old('website', $user->website) }}" class="input" placeholder="https://">
                        @error('website')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="x_handle" class="field-label">X handle <span class="font-normal text-ink-3">optional</span></label>
                        <input id="x_handle" name="x_handle" value="{{ old('x_handle', $user->x_handle ? '@'.$user->x_handle : '') }}" class="input" placeholder="@yourname">
                        @error('x_handle')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="flex justify-end"><button class="btn btn-primary">Save profile</button></div>
            </form>
        </section>

        <section class="mt-10">
            <h2 class="group-header">{{ $user->password ? 'Change password' : 'Set a password' }}</h2>
            <form method="POST" action="{{ route('account.password') }}" class="card space-y-5 p-5">
                @csrf @method('PUT')
                @unless ($user->password)
                    <p class="text-sm text-ink-2">You sign in with {{ ucfirst($user->provider ?? 'a social account') }}. Add a password to also sign in with your email.</p>
                @endunless
                @if ($user->password)
                    <div>
                        <label for="current_password" class="field-label">Current password</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="input">
                        @error('current_password')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                @endif
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="field-label">New password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required class="input">
                        @error('password')<p class="field-error">{{ $message }}</p>@else<p class="field-hint">At least 8 characters.</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="field-label">Confirm</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="input">
                    </div>
                </div>
                <div class="flex justify-end"><button class="btn btn-primary">{{ $user->password ? 'Update password' : 'Set password' }}</button></div>
            </form>
        </section>

        <section class="mt-10">
            <h2 class="group-header">Session</h2>
            <div class="card flex flex-wrap items-center justify-between gap-3 p-5">
                <p class="text-sm text-ink-2">Need your data exported or your account deleted? Email <a href="mailto:{{ config('rankedlaunch.contact_email') }}" class="font-medium text-ink underline underline-offset-2">{{ config('rankedlaunch.contact_email') }}</a>.</p>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-secondary btn-sm"><x-icon name="logout" :size="15" /> Sign out</button></form>
            </div>
        </section>
    </div>
</x-layout>
