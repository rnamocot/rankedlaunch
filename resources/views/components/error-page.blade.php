@props(['code', 'title', 'message'])
{{-- Shared layout for error pages: centred in the space between header and footer. --}}
<x-layout :title="$title" :noindex="true">
    <section class="intro mx-auto flex min-h-[calc(100svh-22rem)] max-w-xl flex-col items-center justify-center px-4 py-20 text-center sm:px-6">
        <p class="text-sm font-semibold tabular-nums text-ink-2">{{ $code }}</p>
        <h1 class="large-title mt-3">{{ $title }}</h1>
        <p class="mt-4 max-w-md text-ink-2">{{ $message }}</p>
        {{ $slot }}
    </section>
</x-layout>
