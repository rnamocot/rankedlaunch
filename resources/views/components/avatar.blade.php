@props(['user'])
@if ($user?->avatar)
    <img src="{{ $user->avatar }}" alt="" loading="lazy" {{ $attributes->merge(['class' => 'shrink-0 rounded-full object-cover outline outline-1 -outline-offset-1 outline-line']) }}>
@else
    <span {{ $attributes->merge(['class' => 'grid shrink-0 place-items-center rounded-full bg-fill font-semibold']) }}>{{ $user?->initials() ?? '?' }}</span>
@endif
