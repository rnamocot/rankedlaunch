<x-admin.shell title="System" subtitle="Is everything the site depends on working?">
    <x-slot:actions>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.system.run') }}">@csrf<button class="btn btn-outline btn-sm">Run launch emails now</button></form>
            <form method="POST" action="{{ route('admin.system.email') }}">@csrf<button class="btn btn-outline btn-sm">Send me a test email</button></form>
            <form method="POST" action="{{ route('admin.system.cache') }}">@csrf<button class="btn btn-outline btn-sm">Clear caches</button></form>
        </div>
    </x-slot:actions>

    @php($failing = collect($checks)->reject(fn ($c) => $c[1])->count())
    <p class="mb-4 text-sm font-medium">{{ $failing ? "{$failing} ".Str::plural('check', $failing).' need attention' : 'All checks pass' }}</p>

    <ul class="card overflow-hidden">
        @foreach ($checks as [$label, $ok, $detail, $fix])
            <li class="list-row flex gap-4 px-5 py-4" style="--inset: 3.75rem">
                <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full {{ $ok ? 'bg-ink text-on-ink' : 'ring-2 ring-danger text-danger' }}">
                    <x-icon :name="$ok ? 'check' : 'close'" :size="12" :stroke="3" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ $label }} <span class="sr-only">{{ $ok ? 'OK' : 'needs attention' }}</span></p>
                    <p class="mt-0.5 break-words text-sm text-ink-2">{{ $detail }}</p>
                    @unless ($ok)
                        <p class="mt-1.5 text-sm"><span class="font-medium">Fix:</span> <span class="break-words font-mono text-[0.8125rem]">{{ $fix }}</span></p>
                    @endunless
                </div>
            </li>
        @endforeach
    </ul>

    <div class="mt-8 grid grid-cols-1 gap-8 xl:grid-cols-2">
        <section>
            <h2 class="group-header">Environment</h2>
            <dl class="card overflow-hidden text-sm">
                @foreach ($info as $l => $v)
                    <div class="list-row flex justify-between px-4 py-2.5"><dt class="text-ink-2">{{ $l }}</dt><dd class="font-medium tabular-nums">{{ $v }}</dd></div>
                @endforeach
            </dl>
        </section>

        <section>
            <h2 class="group-header">Recent errors</h2>
            <ul class="card overflow-hidden text-xs">
                @forelse ($logErrors as $e)
                    <li class="list-row px-4 py-2.5"><span class="text-ink-2 tabular-nums">{{ $e['at'] }}</span><br><span class="break-words font-mono">{{ $e['message'] }}</span></li>
                @empty
                    <li class="px-4 py-6 text-center text-sm text-ink-2">No errors logged.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-admin.shell>
