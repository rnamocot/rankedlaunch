@props(['url', 'active' => false, 'icon', 'on', 'off', 'label' => null])
{{-- Signed-in toggle (Save, Notify me). Guests are sent to sign in. Labels swap with aria-pressed. --}}
<button type="button"
    {{ $attributes->merge(['class' => 'toggle-btn']) }}
    data-toggle-url="{{ $url }}"
    @guest data-login="{{ route('login') }}" @endguest
    aria-pressed="{{ $active ? 'true' : 'false' }}"
    @if ($label) aria-label="{{ $label }}" @endif>
    <x-icon :name="$icon" :size="15" :stroke="2" />
    <span data-on>{{ $on }}</span><span data-off>{{ $off }}</span>
</button>
