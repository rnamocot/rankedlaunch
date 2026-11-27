{{--
    Maker lounge: anonymous global chat. Floating button → side panel (desktop) / sheet (phone).
    Polls /chat/messages every 4s while open. Messages are rendered as text (never HTML).
--}}
@php
    $online = app(\App\Http\Controllers\ChatController::class)->onlineCount();
@endphp
<div data-chat data-src="{{ route('chat.index') }}" data-post="{{ route('chat.store') }}" data-rename="{{ route('chat.rename') }}">
    {{-- Launcher --}}
    <button type="button" data-chat-open
            class="fixed right-4 bottom-4 z-50 flex h-12 items-center gap-2.5 rounded-full bg-ink pr-5 pl-4 text-sm font-semibold text-on-ink shadow-[0_10px_30px_-10px_rgba(0,0,0,0.45)] transition hover:opacity-90 sm:right-6 sm:bottom-6"
            aria-haspopup="dialog" aria-controls="chat-panel">
        <x-icon name="send" :size="17" />
        <span>Chat</span>
        <span class="flex items-center gap-1.5 text-xs font-medium opacity-75"><span class="size-1.5 rounded-full bg-[var(--accent)]"></span><span data-chat-online-count>{{ $online }}</span> online</span>
        <span data-chat-unread hidden class="absolute -top-1 -right-1 grid size-5 place-items-center rounded-full bg-[var(--accent)] text-[0.625rem] font-bold text-white"></span>
    </button>

    {{-- Panel --}}
    <section id="chat-panel" role="dialog" aria-modal="false" aria-label="Maker lounge chat" hidden data-chat-panel
             class="fixed inset-0 z-50 flex flex-col bg-surface sm:inset-auto sm:right-6 sm:bottom-6 sm:h-[34rem] sm:w-[24rem] sm:rounded-2xl sm:shadow-[0_24px_60px_-20px_rgba(0,0,0,0.35)] sm:outline sm:outline-1 sm:-outline-offset-1 sm:outline-line">
        <header class="flex items-center gap-3 border-b border-line px-4 py-3">
            <x-logo :size="26" :wordmark="false" class="text-ink" />
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold">Maker lounge</p>
                <p class="flex items-center gap-1.5 text-xs text-ink-2"><span class="size-1.5 rounded-full bg-[var(--accent)]"></span><span data-chat-online>{{ $online }}</span> online · anonymous</p>
            </div>
            <button type="button" data-chat-close class="grid size-9 place-items-center rounded-full text-ink-2 hover:bg-fill hover:text-ink" aria-label="Close chat">
                <x-icon name="close" :size="18" />
            </button>
        </header>

        <div data-chat-log class="flex-1 space-y-3 overflow-y-auto px-4 py-4" aria-live="polite">
            <p data-chat-empty class="py-10 text-center text-sm text-ink-2">Say hi to other makers. Share what you’re building.</p>
        </div>

        <div class="border-t border-line px-4 pt-3 pb-4">
            <p data-chat-error hidden class="mb-2 text-xs text-danger" role="alert"></p>
            <form data-chat-form class="flex items-center gap-2">
                <label for="chat-input" class="sr-only">Message</label>
                <input id="chat-input" name="body" maxlength="280" autocomplete="off" placeholder="Say something nice…" class="input !h-10 min-w-0 flex-1">
                <button class="btn btn-primary !h-10 !px-3.5" aria-label="Send"><x-icon name="send" :size="16" /></button>
            </form>

            <div class="mt-2.5 flex items-center justify-between gap-3 text-[0.6875rem] text-ink-2">
                <span data-chat-identity class="truncate">Chatting as <span data-chat-me class="font-semibold text-ink">…</span> · <button type="button" data-chat-rename-open class="underline underline-offset-2 hover:text-ink">Change name</button></span>
            </div>
            <form data-chat-rename hidden class="mt-2 flex items-center gap-2">
                <label for="chat-name" class="sr-only">New name</label>
                <input id="chat-name" name="name" maxlength="20" placeholder="New name" class="input !h-9 min-w-0 flex-1 !text-sm">
                <button class="btn btn-secondary btn-sm">Save</button>
                <button type="button" data-chat-rename-cancel class="btn btn-outline btn-sm">Cancel</button>
            </form>
            <p class="mt-2 text-[0.6875rem] leading-relaxed text-ink-3">Be kind. Swearing, sexual talk, links and phone numbers are blocked, and repeat offenders get muted.</p>
        </div>
    </section>
</div>
