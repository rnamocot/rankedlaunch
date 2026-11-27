@props(['product', 'comments'])
{{-- Product comments: one level of replies; the maker's own replies are labelled. --}}
@php
    $total = $comments->sum(fn ($c) => 1 + $c->replies->count());
    $me = auth()->user();
@endphp
<section id="comments" class="mt-12 scroll-mt-24">
    <h2 class="group-header !px-0">
        <span>Comments <span class="ml-1 font-normal tabular-nums text-ink-2">{{ $total }}</span></span>
    </h2>

    {{-- New comment --}}
    @auth
        <form method="POST" action="{{ route('comments.store', $product) }}" class="card p-4">
            @csrf
            <label for="comment-body" class="sr-only">Write a comment</label>
            <textarea id="comment-body" name="body" rows="3" maxlength="1000" required class="input" placeholder="{{ $me->id === $product->user_id ? 'Say hi, share what’s new, or answer questions…' : 'Ask the maker something or share feedback…' }}">{{ old('parent_id') ? '' : old('body') }}</textarea>
            @error('body')<p class="field-error">{{ $message }}</p>@enderror
            <div class="mt-3 flex items-center justify-between gap-3">
                <p class="text-xs text-ink-3">Be kind. Links, phone numbers and insults are blocked.</p>
                <button class="btn btn-primary btn-sm">Comment</button>
            </div>
        </form>
    @else
        <div class="card flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
            <span class="text-ink-2">Sign in to join the conversation.</span>
            <a href="{{ route('login') }}" class="btn btn-secondary btn-sm">Sign in</a>
        </div>
    @endauth

    {{-- Thread --}}
    @if ($comments->isNotEmpty())
        <ul class="mt-4 space-y-3">
            @foreach ($comments as $comment)
                <li class="card p-4" id="comment-{{ $comment->id }}">
                    @include('partials.comment', ['comment' => $comment, 'product' => $product])

                    @if ($comment->replies->isNotEmpty())
                        <ul class="mt-4 space-y-4 border-l border-line pl-4">
                            @foreach ($comment->replies as $reply)
                                <li id="comment-{{ $reply->id }}">@include('partials.comment', ['comment' => $reply, 'product' => $product])</li>
                            @endforeach
                        </ul>
                    @endif

                    @auth
                        <details class="mt-3">
                            <summary class="cursor-pointer list-none text-xs font-medium text-ink-2 hover:text-ink [&::-webkit-details-marker]:hidden">Reply</summary>
                            <form method="POST" action="{{ route('comments.store', $product) }}" class="mt-2 flex gap-2">
                                @csrf
                                <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                <label for="reply-{{ $comment->id }}" class="sr-only">Reply</label>
                                <input id="reply-{{ $comment->id }}" name="body" maxlength="1000" required class="input !h-9 min-w-0 flex-1 !text-sm" placeholder="Write a reply…">
                                <button class="btn btn-secondary btn-sm">Reply</button>
                            </form>
                        </details>
                    @endauth
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-4 px-1 text-sm text-ink-2">No comments yet. Be the first to say something nice.</p>
    @endif
</section>
