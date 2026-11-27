{{ $heading }}

@foreach ($lines as $line)
{{ $line }}

@endforeach
@isset($list)
@foreach ($list as $item)
- {{ $item }}
@endforeach

@endisset
@isset($stats)
@foreach ($stats as $label => $value)
{{ $label }}: {{ $value }}
@endforeach

@endisset
{{ $cta[0] }}: {{ $cta[1] }}
@isset($secondary)
{{ $secondary[0] }}: {{ $secondary[1] }}
@endisset

--
You're getting this because you launched {{ $product->name }} on RankedLaunch.
{{ route('home') }}
