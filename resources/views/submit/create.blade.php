@php($editing = $product->exists)
<x-layout :title="$editing ? 'Edit '.$product->name : 'Submit your product'" :noindex="true">
    <div class="mx-auto max-w-2xl px-4 pt-12 sm:px-6">
        <p class="text-sm font-medium text-ink-2">{{ $editing ? 'Edit listing' : 'Step 1 of 2' }}</p>
        <h1 class="large-title mt-2">{{ $editing ? $product->name : 'Tell us about your product' }}</h1>
        <p class="mt-3 text-ink-2">This becomes your permanent page on RankedLaunch. You’ll pick a launch date next.</p>
        @unless ($editing)
            <p class="mt-2 text-sm text-ink-2">Already live for months or years? That’s welcome. Your launch day here is about getting new users and a backlink, not about being brand new.</p>
        @endunless

        <form method="POST" action="{{ $editing ? route('submit.update', $product) : route('submit') }}" enctype="multipart/form-data" class="mt-10 space-y-8">
            @csrf
            @if ($editing) @method('PUT') @endif

            <section>
                <h2 class="group-header">Basics</h2>
                <div class="card space-y-5 p-5 sm:p-6">
                    <div class="flex items-center gap-5">
                        <span class="app-icon grid size-20 place-items-center bg-fill">
                            @if ($editing)
                                <img id="logo-preview" src="{{ $product->logoUrl() }}" alt="">
                            @else
                                <img id="logo-preview" alt="" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <label for="logo" class="field-label">Logo</label>
                            <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" data-preview="logo-preview" {{ $editing ? '' : 'required' }}
                                   class="block w-full text-sm text-ink-2 file:mr-3 file:rounded-full file:border-0 file:bg-fill file:px-4 file:py-2 file:text-sm file:font-semibold file:text-ink hover:file:bg-fill-strong">
                            @error('logo')<p class="field-error">{{ $message }}</p>@else<p class="field-hint">Square PNG, JPG or WebP, at least 128×128. Max 1MB.</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label for="name" class="field-label">Product name</label>
                            <input id="name" name="name" value="{{ old('name', $product->name) }}" required maxlength="60" class="input" placeholder="Acme">
                            @error('name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="url" class="field-label">Website</label>
                            <input id="url" name="url" type="url" value="{{ old('url', $product->url) }}" required class="input" placeholder="https://acme.com">
                            @error('url')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="tagline" class="field-label">Tagline</label>
                        <input id="tagline" name="tagline" value="{{ old('tagline', $product->tagline) }}" required maxlength="100" class="input" placeholder="What it does, in one line">
                        @error('tagline')<p class="field-error">{{ $message }}</p>@else<p class="field-hint">Up to 100 characters. Be specific, skip the buzzwords.</p>@enderror
                    </div>

                    <div>
                        <label for="description" class="field-label">Description</label>
                        <textarea id="description" name="description" rows="7" required minlength="40" maxlength="3000" class="input" placeholder="Who it’s for, the problem it solves, and what makes it different.">{{ old('description', $product->description) }}</textarea>
                        @error('description')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section>
                <h2 class="group-header">Details</h2>
                <div class="card grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
                    <div>
                        <label for="category_id" class="field-label">Category</label>
                        <x-category-select id="category_id" required :categories="$categories" :value="old('category_id', $product->category_id)" />
                        <p class="field-hint">Pick the closest fit. Not listed? Choose “Other” and we’ll place it.</p>
                        @error('category_id')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="pricing_type" class="field-label">Pricing model</label>
                        <select id="pricing_type" name="pricing_type" class="input">
                            @foreach (\App\Models\Product::PRICING_TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('pricing_type', $product->pricing_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="screenshots" class="field-label">Screenshots <span class="font-normal text-ink-3">optional</span></label>
                        <input id="screenshots" name="screenshots[]" type="file" multiple accept="image/png,image/jpeg,image/webp"
                               class="block w-full text-sm text-ink-2 file:mr-3 file:rounded-full file:border-0 file:bg-fill file:px-4 file:py-2 file:text-sm file:font-semibold file:text-ink hover:file:bg-fill-strong">
                        @if ($errors->has('screenshots') || $errors->has('screenshots.*'))
                            <p class="field-error">{{ $errors->first('screenshots') ?: $errors->first('screenshots.*') }}</p>
                        @else
                            <p class="field-hint">Up to 4 images, 16:10 works best. Max 3MB each.{{ $editing && $product->screenshots ? ' Uploading new ones replaces the current set.' : '' }}</p>
                        @endif
                    </div>
                </div>
            </section>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ $editing ? route('dashboard') : route('home') }}" class="btn btn-secondary btn-lg">Cancel</a>
                <button class="btn btn-primary btn-lg">{{ $editing && $product->status !== 'draft' ? 'Save changes' : 'Continue' }} <x-icon name="arrow-right" :size="16" :stroke="2" /></button>
            </div>
        </form>
    </div>
</x-layout>
