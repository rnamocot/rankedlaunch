@props(['title', 'description', 'updated' => null])
{{-- Shared shell for About / Terms / Privacy / Refunds --}}
<x-layout :title="$title" :description="$description">
    <article class="mx-auto max-w-2xl px-4 pt-14 sm:px-6">
        <div class="intro">
            <h1 class="large-title">{{ $title }}</h1>
            @if ($updated)<p class="mt-3 text-sm text-ink-2">Last updated {{ $updated }}</p>@endif
        </div>
        <div class="mt-10 space-y-5 text-[0.9875rem] leading-relaxed text-ink-2 [&_a]:text-ink [&_a]:underline [&_a]:underline-offset-2 [&_h2]:!mt-10 [&_h2]:text-lg [&_h2]:font-semibold [&_h2]:tracking-[-0.015em] [&_h2]:text-ink [&_li]:ml-5 [&_li]:list-disc [&_li]:pl-1 [&_strong]:font-semibold [&_strong]:text-ink [&_ul]:space-y-1.5">
            {{ $slot }}
        </div>
    </article>
</x-layout>
