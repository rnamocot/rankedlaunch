@props(['product'])
{{-- Badge embed picker: light/dark × HTML / Markdown / image link --}}
@php
    $page = route('product', $product);
    $alt = e($product->name).' on RankedLaunch';
    $themes = ['light' => 'Light', 'dark' => 'Dark'];
    $codes = [];
    foreach ($themes as $theme => $label) {
        $q = $theme === 'dark' ? '?theme=dark' : '';
        $svg = route('badge', $product).$q;
        $png = route('badge.png', $product).$q;
        $codes[$theme] = [
            'HTML (websites)' => '<a href="'.$page.'" target="_blank"><img src="'.$svg.'" alt="'.$alt.'" width="220" height="54"></a>',
            'Markdown (GitHub, docs)' => '[!['.$alt.']('.$svg.')]('.$page.')',
            'Image link (apps, emails, Notion)' => $png,
        ];
    }
    [$eyebrow, $title] = \App\Http\Controllers\BadgeController::lines($product);
@endphp
<div {{ $attributes->merge(['class' => 'card p-4']) }}>
    <p class="text-sm font-semibold">{{ $eyebrow === 'RANKEDLAUNCH' ? $title : 'Launched on RankedLaunch' }}</p>
    <p class="mt-0.5 text-xs leading-relaxed text-ink-2">Updates itself if you finish top 5 of the day, week, month or year.</p>

    @foreach ($themes as $theme => $label)
        <div class="mt-4">
            <p class="mb-2 text-xs font-semibold text-ink-2">{{ $label }}</p>
            <div class="grid place-items-center rounded-xl p-3 {{ $theme === 'dark' ? 'bg-[#0a0a0a]' : 'bg-fill' }}">
                <img src="{{ route('badge', $product) }}{{ $theme === 'dark' ? '?theme=dark' : '' }}" alt="{{ $label }} badge preview" width="220" height="54">
            </div>
            <div class="mt-2 space-y-2">
                @foreach ($codes[$theme] as $format => $code)
                    @php($id = 'badge-'.$theme.'-'.$loop->index)
                    <div class="flex items-center gap-2">
                        <input id="{{ $id }}" readonly value="{{ $code }}" aria-label="{{ $label }} badge {{ $format }}" class="input !h-8 min-w-0 flex-1 font-mono !text-[0.6875rem]">
                        <button type="button" data-copy="{{ $id }}" class="btn btn-secondary btn-sm shrink-0" title="Copy {{ $format }}">{{ Str::before($format, ' (') }}</button>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
