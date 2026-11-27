<x-admin.shell title="Activity" subtitle="Everything that happens on RankedLaunch, newest first.">
    <nav class="segmented max-w-full overflow-x-auto" aria-label="Type">
        <a href="{{ route('admin.activity') }}" @if(! $type) aria-current="page" @endif>All</a>
        @foreach (\App\Models\ActivityLog::TYPES as $key => $label)
            <a href="{{ route('admin.activity', ['type' => $key]) }}" @if($type === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <div class="mt-5">@include('admin.partials.activity-list', ['logs' => $logs])</div>
    <div class="mt-6">{{ $logs->links() }}</div>
</x-admin.shell>
