{{-- Ctrl+K / "/" search: apps, categories and makers as you type. Enter with no selection goes to Discover. --}}
<div id="search-palette" hidden class="fixed inset-0 z-[60]" data-search-palette data-url="{{ route('search') }}" data-discover="{{ route('discover') }}" role="dialog" aria-modal="true" aria-label="Search">
    <div class="absolute inset-0 bg-black/30 backdrop-blur-[2px]" data-search-close></div>
    <div class="relative mx-auto mt-[12vh] w-[min(36rem,calc(100vw-2rem))]">
        <div class="card overflow-hidden !shadow-2xl">
            <form class="relative border-b border-line" data-search-form role="search">
                <x-icon name="search" :size="18" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-ink-2" />
                <label for="palette-q" class="sr-only">Search apps, categories and makers</label>
                <input id="palette-q" name="q" autocomplete="off" placeholder="Search apps, categories, makers…" class="h-14 w-full bg-transparent pr-16 pl-11 text-[1rem] outline-none" data-search-input
                       role="combobox" aria-expanded="false" aria-controls="palette-results" aria-autocomplete="list">
                <kbd class="absolute top-1/2 right-4 -translate-y-1/2 rounded-md border border-line px-1.5 py-0.5 text-[0.6875rem] font-medium text-ink-3">Esc</kbd>
            </form>
            <div id="palette-results" role="listbox" class="max-h-[60vh] overflow-y-auto py-2" data-search-results>
                <p class="px-4 py-6 text-center text-sm text-ink-2">Type at least 2 letters. Press Enter to search everything.</p>
            </div>
        </div>
    </div>
</div>
