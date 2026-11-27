<x-admin.shell title="Settings" subtitle="Changes apply to the whole site immediately. No code or deploy needed.">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-2xl space-y-8">
        @csrf @method('PUT')

        @foreach ($fields as $group => $items)
            <section>
                <h2 class="group-header">{{ $group }}</h2>
                <div class="card overflow-hidden">
                    @foreach ($items as $key => [$label, $type, $help])
                        @php
                            $name = str_replace('.', '__', $key);
                            $current = config("rankedlaunch.$key");
                            $value = old($name, $type === 'money' ? $current / 100 : $current);
                        @endphp
                        <div class="list-row grid grid-cols-1 gap-2 px-5 py-4 sm:grid-cols-[1fr_11rem] sm:items-center">
                            <div>
                                <label for="{{ $name }}" class="text-sm font-semibold">{{ $label }}</label>
                                <p class="mt-0.5 text-xs text-ink-2">{{ $help }}</p>
                                @error($name)<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            @if ($type === 'bool')
                                <label class="flex items-center gap-2 sm:justify-end">
                                    <input type="hidden" name="{{ $name }}" value="0">
                                    <input id="{{ $name }}" type="checkbox" name="{{ $name }}" value="1" @checked($value) class="size-5 accent-[var(--ink)]">
                                    <span class="text-sm">{{ $value ? 'On' : 'Off' }}</span>
                                </label>
                            @elseif ($type === 'money')
                                <div class="relative">
                                    <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-ink-2">$</span>
                                    <input id="{{ $name }}" name="{{ $name }}" type="number" step="0.01" min="0" value="{{ $value }}" class="input !h-10 !pl-7 text-right tabular-nums">
                                </div>
                            @elseif ($type === 'int')
                                <input id="{{ $name }}" name="{{ $name }}" type="number" min="0" value="{{ $value }}" class="input !h-10 text-right tabular-nums">
                            @else
                                <input id="{{ $name }}" name="{{ $name }}" value="{{ $value }}" class="input !h-10">
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="flex items-center justify-between gap-4">
            <p class="text-xs text-ink-2">Polar uses fixed product prices: if you change a price here, update the matching Polar product too.</p>
            <button class="btn btn-primary btn-lg shrink-0">Save settings</button>
        </div>
    </form>
</x-admin.shell>
