@php($labels = ['google' => 'Google', 'github' => 'GitHub', 'x' => 'X'])
@if (count($socialProviders))
    <div class="space-y-2.5">
        @foreach ($socialProviders as $provider)
            <a href="{{ route('social.redirect', $provider) }}" class="btn btn-outline btn-lg w-full">
                <x-brand :name="$provider" :size="18" /> Continue with {{ $labels[$provider] }}
            </a>
        @endforeach
    </div>
    <div class="my-6 flex items-center gap-3 text-xs text-ink-3">
        <span class="h-px flex-1 bg-line"></span> or <span class="h-px flex-1 bg-line"></span>
    </div>
@endif
