@props(['to', 'label' => 'Voting closes in'])
{{--
    Realistic split-flap (flip) countdown. Each digit is a card with a hinge;
    when it changes, the top half folds down in 3D and reveals the new number.
    Animated by [data-flip-clock] in app.js; static digits for reduced motion.
--}}
<div {{ $attributes->merge(['class' => 'flip-clock']) }} data-flip-clock data-to="{{ $to->copy()->utc()->toIso8601String() }}" role="timer" aria-live="off" aria-label="{{ $label }}">
    <p class="mb-2.5 text-xs font-medium text-ink-2">{{ $label }}</p>
    <div class="flex items-start gap-2 sm:gap-3">
        @foreach (['hours' => 'hrs', 'minutes' => 'min', 'seconds' => 'sec'] as $unit => $short)
            <div class="flex flex-col items-center">
                <div class="flex gap-1">
                    @for ($d = 0; $d < 2; $d++)
                        <span class="flip" data-digit="{{ $unit }}-{{ $d }}">
                            <span class="flip-half flip-top"><span>0</span></span>
                            <span class="flip-half flip-bottom"><span>0</span></span>
                            <span class="flip-half flip-top flip-fold"><span>0</span></span>
                            <span class="flip-half flip-bottom flip-unfold"><span>0</span></span>
                        </span>
                    @endfor
                </div>
                <span class="mt-1.5 text-[0.625rem] font-semibold uppercase tracking-[0.12em] text-ink-3">{{ $short }}</span>
            </div>
            @if (! $loop->last)
                <span class="flip-colon" aria-hidden="true"><i></i><i></i></span>
            @endif
        @endforeach
    </div>
</div>
