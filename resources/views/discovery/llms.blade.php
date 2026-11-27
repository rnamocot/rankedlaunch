# RankedLaunch

> RankedLaunch ({{ url('/') }}) is a daily launch board for new software products from indie makers and startups. Each day new products go live at 00:00 UTC and are ranked by community upvotes. Every launch gets a permanent, indexable product page.

## How it works

- Makers submit a product (name, website, tagline, description, logo, category). New and established products are both welcome.
- Every listing is reviewed by a person before it goes live.
- Free launches join a queue (about {{ config('rankedlaunch.free_wait_days') }} days). Paid launches ({{ config('rankedlaunch.plans.pro.name') }} and {{ config('rankedlaunch.plans.featured.name') }}) pick their own date and get a dofollow backlink. {{ config('rankedlaunch.plans.featured.name') }} launches are also pinned on the homepage.
- Rankings are by upvotes only; paid plans never change rank. Pro launches are labelled "Featured" and shown in a rotating homepage section.
- Products are ranked by upvotes against everything launching the same day and the same week. The top 3 get a "Product of the Day" or "Product of the Week" badge.
- Pricing is a one-time payment per launch. No subscription.

## Key pages

- [Today's launches]({{ route('home') }}): products launching today, ranked live
- [Rankings: this week]({{ route('rankings', 'week') }})
- [Rankings: this month]({{ route('rankings', 'month') }})
- [Rankings: all time]({{ route('rankings', 'all-time') }})
- [Launch your product]({{ route('launch') }}): what makers get and how it works
- [Pricing]({{ route('pricing') }})
- [Discover tools]({{ route('discover') }}): search and filter every listed product by category and pricing. Each product also has an alternatives page at /apps/{slug}/alternatives

## Categories

@foreach ($categories as $category)
- [{{ $category->name }}]({{ route('category', $category) }})
@endforeach

## Machine-readable data

- [JSON feed of today's and this week's launches]({{ route('launches.json') }})
- [RSS feed of recent launches]({{ route('feed') }})
- [Sitemap]({{ route('sitemap') }})

@if ($top->isNotEmpty())
## Top products this week

@foreach ($top as $i => $product)
{{ $i + 1 }}. [{{ $product->name }}]({{ route('product', $product) }}): {{ $product->tagline }} ({{ $product->category?->name }}, {{ $product->votes_count }} upvotes)
@endforeach
@endif
