@props(['size' => 28, 'wordmark' => true])
{{-- RankedLaunch mark: a podium inside a squircle --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg class="logo-mark" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 32 32" aria-hidden="true">
        <rect width="32" height="32" rx="8.5" fill="currentColor" />
        <rect class="logo-bar" x="6.75" y="15.5" width="5.5" height="9.5" rx="1.25" style="fill: var(--on-ink)" opacity=".55" />
        <rect class="logo-bar logo-bar-top" x="13.25" y="8" width="5.5" height="17" rx="1.25" style="fill: var(--on-ink)" />
        <rect class="logo-bar" x="19.75" y="12" width="5.5" height="13" rx="1.25" style="fill: var(--on-ink)" opacity=".78" />
    </svg>
    @if ($wordmark)
        <span class="logo-word text-[1.0625rem] font-semibold tracking-[-0.025em]">RankedLaunch</span>
    @endif
</span>
