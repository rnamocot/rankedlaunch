@if ($paginator->hasPages())
    <nav class="flex items-center justify-between" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="btn btn-secondary btn-sm opacity-40">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary btn-sm">Previous</a>
        @endif
        <span class="text-sm tabular-nums text-ink-2">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary btn-sm">Next</a>
        @else
            <span class="btn btn-secondary btn-sm opacity-40">Next</span>
        @endif
    </nav>
@endif
