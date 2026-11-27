{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>RankedLaunch · New product launches</title>
        <link>{{ route('home') }}</link>
        <description>New products from indie makers, ranked daily by the community.</description>
        <language>en</language>
        <atom:link href="{{ route('feed') }}" rel="self" type="application/rss+xml" />
        @foreach ($products as $product)
        <item>
            <title>{{ $product->name }}: {{ $product->tagline }}</title>
            <link>{{ route('product', $product) }}</link>
            <guid isPermaLink="true">{{ route('product', $product) }}</guid>
            <pubDate>{{ $product->launch_date->toRfc2822String() }}</pubDate>
            @if ($product->category)<category>{{ $product->category->name }}</category>@endif
            <description>{{ Str::limit($product->description, 400) }}</description>
        </item>
        @endforeach
    </channel>
</rss>
