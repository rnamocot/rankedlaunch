<x-layout title="Notifications" :noindex="true">
    <section class="mx-auto max-w-2xl px-4 pt-14 sm:px-6">
        <h1 class="title-2 !text-[1.75rem]">Notifications</h1>
        <p class="mt-1 text-sm text-ink-2">Replies to your comments, comments and upvote milestones on your launches, awards, and launches you asked to hear about.</p>

        <div class="tray mt-8">
            <div class="card overflow-hidden">
                @forelse ($notifications as $n)
                    @include('partials.notification', ['n' => $n])
                @empty
                    <div class="px-6 py-14 text-center">
                        <p class="font-semibold">You’re all caught up</p>
                        <p class="mt-1 text-sm text-ink-2">Tap “Notify” on an upcoming launch to hear when it goes live.</p>
                        <a href="{{ route('upcoming') }}" class="btn btn-secondary btn-sm mt-5">See upcoming launches</a>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="mt-6">{{ $notifications->links() }}</div>
    </section>
</x-layout>
