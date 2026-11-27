@props(['categories', 'name' => 'category_id', 'value' => null, 'by' => 'id', 'placeholder' => 'Choose a category', 'placeholderSelectable' => false])
{{-- Category dropdown grouped by area (AI, Developer, Productivity …) --}}
<select name="{{ $name }}" {{ $attributes->merge(['class' => 'input']) }}>
    <option value="" @disabled(! $placeholderSelectable) @selected(blank($value))>{{ $placeholder }}</option>
    @foreach ($categories->groupBy(fn ($c) => $c->group ?: 'Other') as $group => $items)
        <optgroup label="{{ $group }}">
            @foreach ($items as $c)
                <option value="{{ $c->{$by} }}" @selected((string) $value === (string) $c->{$by})>{{ $c->name }}</option>
            @endforeach
        </optgroup>
    @endforeach
</select>
