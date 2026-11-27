{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ route('home') }}</loc><changefreq>hourly</changefreq><priority>1.0</priority></url>
    <url><loc>{{ route('pricing') }}</loc><changefreq>monthly</changefreq></url>
    <url><loc>{{ route('discover') }}</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
    <url><loc>{{ route('how-it-works') }}</loc><changefreq>monthly</changefreq></url>
    <url><loc>{{ route('launch') }}</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>
    <url><loc>{{ route('about') }}</loc><changefreq>yearly</changefreq></url>
    <url><loc>{{ route('terms') }}</loc><changefreq>yearly</changefreq></url>
    <url><loc>{{ route('privacy') }}</loc><changefreq>yearly</changefreq></url>
    <url><loc>{{ route('refunds') }}</loc><changefreq>yearly</changefreq></url>
    @foreach (array_keys(\App\Http\Controllers\RankingController::PERIODS) as $period)
    <url><loc>{{ route('rankings', $period) }}</loc><changefreq>daily</changefreq></url>
    @endforeach
    @foreach ($categories as $category)
    <url><loc>{{ route('category', $category) }}</loc><lastmod>{{ $category->updated_at?->toAtomString() }}</lastmod><changefreq>daily</changefreq></url>
    @endforeach
    @foreach ($products as $product)
    <url><loc>{{ route('product', $product) }}</loc><lastmod>{{ $product->updated_at?->toAtomString() }}</lastmod></url>
    @endforeach
    @foreach ($alternatives as $product)
    <url><loc>{{ route('alternatives', $product) }}</loc><changefreq>weekly</changefreq></url>
    @endforeach
    @foreach ($makers as $maker)
    <url><loc>{{ route('profile', $maker->username) }}</loc><changefreq>weekly</changefreq></url>
    @endforeach
</urlset>
