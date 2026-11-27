@php($typeLabels = \App\Models\ActivityLog::TYPES)
<ul class="card overflow-hidden">
    @forelse ($logs as $log)
        <li class="list-row flex items-start gap-4 px-4 py-3 text-sm">
            <span class="w-24 shrink-0 text-xs text-ink-2 tabular-nums" title="{{ $log->created_at }}">{{ $log->created_at->diffForHumans(short: true) }}</span>
            <span class="w-24 shrink-0 text-xs font-medium text-ink-2">{{ $typeLabels[$log->type] ?? ucfirst($log->type) }}</span>
            <span class="min-w-0 flex-1 {{ str_starts_with($log->message, 'FAILED') ? 'text-danger' : '' }}">
                {{ $log->message }}
                @if ($log->product)
                    <a href="{{ route('admin.products.edit', $log->product) }}" class="ml-1 text-xs text-ink-2 underline underline-offset-2 hover:text-ink">open</a>
                @endif
            </span>
        </li>
    @empty
        <li class="px-4 py-8 text-center text-sm text-ink-2">No activity yet.</li>
    @endforelse
</ul>
