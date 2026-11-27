<x-admin.shell title="Categories" :subtitle="$categories->count().' categories. Order sets how they appear; groups organise the dropdown. Categories with products can’t be deleted.'">
    <form method="POST" action="{{ route('admin.categories.store') }}" class="card flex flex-wrap items-end gap-3 p-4">
        @csrf
        <div class="flex-1"><label class="field-label" for="new-name">New category</label><input id="new-name" name="name" required class="input !h-10" placeholder="e.g. Customer Support"></div>
        <div class="w-44"><label class="field-label" for="new-group">Group</label><input id="new-group" name="group" list="category-groups" class="input !h-10" placeholder="e.g. Business"></div>
        <button class="btn btn-primary">Add</button>
    </form>

    <ul class="card mt-6 overflow-hidden">
        @foreach ($categories as $c)
            <li class="list-row px-4 py-3">
                <form method="POST" action="{{ route('admin.categories.update', $c) }}" class="flex flex-wrap items-center gap-2">
                    @csrf @method('PUT')
                    <input name="sort" type="number" value="{{ $c->sort }}" aria-label="Order" class="input !h-9 !w-16 !text-sm tabular-nums">
                    <input name="name" value="{{ $c->name }}" aria-label="Name" class="input !h-9 min-w-0 flex-1 !text-sm">
                    <input name="slug" value="{{ $c->slug }}" aria-label="Slug" class="input !h-9 w-36 !text-sm text-ink-2">
                    <input name="group" value="{{ $c->group }}" list="category-groups" aria-label="Group" class="input !h-9 w-32 !text-sm">
                    <span class="w-20 text-right text-xs text-ink-2 tabular-nums">{{ $c->products_count }} products</span>
                    <button class="btn btn-secondary btn-sm">Save</button>
                </form>
                @unless ($c->products_count)
                    <form method="POST" action="{{ route('admin.categories.destroy', $c) }}" class="mt-1 text-right">@csrf @method('DELETE')<button class="text-xs font-medium text-danger hover:underline">Delete</button></form>
                @endunless
            </li>
        @endforeach
    </ul>
    <datalist id="category-groups">
        @foreach ($categories->pluck('group')->filter()->unique() as $g)<option value="{{ $g }}">@endforeach
    </datalist>
</x-admin.shell>
