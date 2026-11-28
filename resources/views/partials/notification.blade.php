{{-- One notification row. $n is a DatabaseNotification; data holds message, url, image and an optional excerpt. --}}
@php($data = $n->data)
<a href="{{ $data['url'] ?? route('notifications') }}" class="list-row flex items-start gap-3 px-4 py-3 hover:bg-surface-2" style="--inset: 3.75rem">
    @if (! empty($data['image']))
        <img src="{{ $data['image'] }}" alt="" loading="lazy" class="size-8 shrink-0 rounded-[0.55rem] object-cover outline outline-1 -outline-offset-1 outline-line">
    @else
        <span class="grid size-8 shrink-0 place-items-center rounded-[0.55rem] bg-fill text-ink-2"><x-icon name="bell" :size="15" /></span>
    @endif
    <span class="min-w-0 flex-1">
        <span class="block text-sm leading-snug {{ $n->read_at ? 'text-ink-2' : 'font-medium text-ink' }}">{{ $data['message'] ?? 'Notification' }}</span>
        @if (! empty($data['excerpt']))
            <span class="mt-0.5 block {{ ($compact ?? false) ? 'truncate' : '' }} text-xs text-ink-2">“{{ $data['excerpt'] }}”</span>
        @endif
        <span class="mt-0.5 block text-xs text-ink-3">{{ $n->created_at->diffForHumans() }}</span>
    </span>
    @unless ($n->read_at)
        <span class="mt-1.5 size-2 shrink-0 rounded-full bg-[var(--accent)]" aria-label="Unread"></span>
    @endunless
</a>
