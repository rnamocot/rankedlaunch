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
                <span class="rounded-md bg-[var(--accent)]/10 px-1.5 py-0.5 text-[0.6875rem] font-semibold uppercase tracking-wide text-[var(--accent)]">Maker</span>
            @endif
            <span class="text-xs text-ink-3" title="{{ $comment->created_at }}">{{ $comment->created_at->diffForHumans() }}</span>
        </p>
        <p class="mt-1 whitespace-pre-line break-words text-[0.9375rem] leading-relaxed">{{ $comment->body }}</p>
        <div class="mt-2 flex items-center gap-3">
            @if (auth()->id() === $comment->user_id)
                <span class="comment-vote !cursor-default" title="Upvotes on your comment"><x-icon name="chevron-up" :size="13" :stroke="2.25" /><span>{{ $comment->votes_count }}</span></span>
            @else
                <button type="button" class="comment-vote" data-toggle-url="{{ route('comments.vote', $comment) }}" @guest data-login="{{ route('login') }}" @endguest
                        aria-pressed="{{ isset($votedIds[$comment->id]) ? 'true' : 'false' }}" aria-label="Upvote comment by {{ $author?->name ?? 'deleted user' }}">
                    <x-icon name="chevron-up" :size="13" :stroke="2.25" /><span data-count>{{ $comment->votes_count }}</span>
                </button>
            @endif
            @if ($canDelete)
                <form method="POST" action="{{ route('comments.destroy', $comment) }}">
                    @csrf @method('DELETE')
                    <button class="text-xs font-medium text-ink-3 hover:text-danger">Delete</button>
                </form>
            @endif
        </div>
    </div>
</div>
