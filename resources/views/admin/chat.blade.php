<x-admin.shell title="Chat" :subtitle="$today.' messages today · '.$online.' online now · chat is '.(config('rankedlaunch.chat_enabled') ? 'on' : 'off').' (Settings)'">
    <div class="grid grid-cols-1 gap-8 xl:grid-cols-[minmax(0,1fr)_18rem]">
        <section>
            <h2 class="group-header">Messages</h2>
            <ul class="card overflow-hidden">
                @forelse ($messages as $m)
                    <li class="list-row flex items-start gap-4 px-4 py-3 text-sm">
                        <div class="min-w-0 flex-1">
                            <p><span class="font-semibold">{{ $m->name }}</span> <span class="text-xs text-ink-2 tabular-nums">{{ $m->created_at->format('M j, H:i') }} · id {{ substr($m->chat_id, 0, 8) }}</span></p>
                            <p class="mt-0.5 break-words text-ink-2">{{ $m->body }}</p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <form method="POST" action="{{ route('admin.chat.mute', $m) }}">@csrf<button class="btn btn-outline btn-sm">Mute 7d</button></form>
                            <form method="POST" action="{{ route('admin.chat.ban', $m) }}">@csrf<button class="btn btn-outline btn-sm !text-danger">Ban</button></form>
                            <form method="POST" action="{{ route('admin.chat.destroy', $m) }}">@csrf @method('DELETE')<button class="btn btn-secondary btn-sm !text-danger">Delete</button></form>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-10 text-center text-sm text-ink-2">No messages yet.</li>
                @endforelse
            </ul>
            <div class="mt-6">{{ $messages->links() }}</div>
        </section>

        <aside class="space-y-8">
            <section>
                <h2 class="group-header">Muted and banned</h2>
                <ul class="card overflow-hidden text-sm">
                    @forelse ($mutes as $mute)
                        <li class="list-row flex items-center justify-between gap-3 px-4 py-2.5">
                            <span class="min-w-0">
                                <span class="block truncate">{{ $mute->reason }}</span>
                                @php($until = \Illuminate\Support\Carbon::parse($mute->until))
                                <span class="block text-xs {{ $until->gt(now()->addYears(50)) ? 'font-semibold text-danger' : 'text-ink-2' }}">{{ $until->gt(now()->addYears(50)) ? 'Banned' : 'Muted until '.$until->format('M j, H:i') }}</span>
                            </span>
                            <form method="POST" action="{{ route('admin.chat.unmute', $mute->id) }}">@csrf<button class="btn btn-outline btn-sm">Lift</button></form>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-ink-2">Nobody is muted.</li>
                    @endforelse
                </ul>
            </section>

            <section>
                <h2 class="group-header">Rules (automatic)</h2>
                <div class="card p-4 text-sm text-ink-2">
                    Blocked: swearing, sexual talk, insults and harassment, threats and hate terms (English and Filipino), links and phone numbers. Refused as spam: shouting in caps, repeated characters, the same message twice. 3 blocked attempts in 10 minutes = 30 minutes muted; muted again within 24 hours = 7 days. One message every 3 seconds, 280 characters max. Messages are removed after 14 days.
                </div>
            </section>

            <section>
                <h2 class="group-header">Danger zone</h2>
                <form method="POST" action="{{ route('admin.chat.clear') }}" class="card space-y-3 p-4">
                    @csrf
                    <label class="flex items-start gap-2 text-xs text-ink-2"><input type="checkbox" name="confirm" value="1" class="mt-0.5 accent-[var(--ink)]"> Delete every chat message.</label>
                    <button class="btn btn-secondary btn-sm w-full !text-danger">Clear chat</button>
                </form>
            </section>
        </aside>
    </div>
</x-admin.shell>
