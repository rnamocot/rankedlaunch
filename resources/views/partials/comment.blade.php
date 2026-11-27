@php
    $author = $comment->user;
    $canDelete = auth()->check() && (auth()->id() === $comment->user_id || auth()->user()->is_admin);
@endphp
<div class="flex items-start gap-3">
    @if ($author?->avatar)
        <img src="{{ $author->avatar }}" alt="" class="size-8 shrink-0 rounded-full">
    @else
        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-fill text-xs font-semibold">{{ $author?->initials() ?? '?' }}</span>
    @endif
    <div class="min-w-0 flex-1">
        <p class="flex flex-wrap items-center gap-x-2 text-sm">
            @if ($author)
                <a href="{{ $author->profileUrl() }}" class="font-semibold hover:underline">{{ $author->name }}</a>
            @else
                <span class="font-semibold">Deleted user</span>
            @endif
            @if ($comment->user_id === $product->user_id)
                <span class="text-[0.6875rem] font-semibold uppercase tracking-wide text-[var(--accent)]">Maker</span>
            @endif
            <span class="text-xs text-ink-3" title="{{ $comment->created_at }}">{{ $comment->created_at->diffForHumans() }}</span>
        </p>
        <p class="mt-1 whitespace-pre-line break-words text-[0.9375rem] leading-relaxed">{{ $comment->body }}</p>
        @if ($canDelete)
            <form method="POST" action="{{ route('comments.destroy', $comment) }}" class="mt-1.5">
                @csrf @method('DELETE')
                <button class="text-xs font-medium text-ink-3 hover:text-danger">Delete</button>
            </form>
        @endif
    </div>
</div>
