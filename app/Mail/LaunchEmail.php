<?php

namespace App\Mail;

use App\Models\Product;
use App\Services\Pricing;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Every email a maker gets about one launch.
 * Types: booked, approved, rejected, tomorrow, live, results.
 */
class LaunchEmail extends Mailable
{
    use Queueable, SerializesModels;

    public const TYPES = ['booked', 'approved', 'rejected', 'tomorrow', 'live', 'results'];

    public function __construct(public Product $product, public string $type) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->copy()['subject']);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.launch',
            text: 'emails.launch-text',
            with: $this->copy() + ['product' => $this->product],
        );
    }

    /** Subject and body copy for this email type. */
    private function copy(): array
    {
        $p = $this->product;
        $name = $p->name;
        $date = $p->launch_date?->format('l, M j');
        // When the launch goes live / voting closes, in the maker's own time zone if we know it.
        $liveAt = $p->launch_date ? $p->user->localTime(\Illuminate\Support\Carbon::parse($p->launch_date->toDateString(), 'UTC')) : null;
        $closesAt = $p->launch_date ? $p->user->localTime(\Illuminate\Support\Carbon::parse($p->launch_date->toDateString(), 'UTC')->addDay()) : null;
        $hub = route('launch.hub', $p);
        $public = route('product', $p);

        return match ($this->type) {
            'booked' => [
                'subject' => "You're booked: {$name} launches {$date}",
                'preheader' => 'Here’s what happens between now and launch day.',
                'heading' => "{$name} is booked for {$date}",
                'lines' => array_filter([
                    'Thanks for launching on RankedLaunch. A real person will review your listing, usually within 24 hours. We’ll email you when it’s approved.',
                    'In the meantime, your launch hub has a share kit, a launch-day checklist and a countdown.',
                    $this->upsellLine(),
                ]),
                'cta' => ['Open your launch hub', $hub],
            ],
            'approved' => [
                'subject' => "{$name} is approved",
                'preheader' => "You go live on {$date} at {$liveAt}.",
                'heading' => "{$name} is approved",
                'lines' => [
                    "You’re on the board. {$name} goes live on {$date} at {$liveAt}.",
                    'Now is a good time to write your launch posts and tell your users the date. Your share kit has ready-to-post text for X and LinkedIn.',
                ],
                'cta' => ['Get your share kit', $hub],
            ],
            'rejected' => [
                'subject' => "About your {$name} submission",
                'preheader' => 'We couldn’t approve this one.',
                'heading' => "We couldn’t approve {$name}",
                'lines' => array_filter([
                    'Reason: '.($p->rejection_reason ?: 'It doesn’t meet our listing guidelines.'),
                    $p->plan !== 'free' ? 'Since you paid for this launch, we’ll refund you in full.' : null,
                    'If you think we got it wrong, just reply to this email.',
                ]),
                'cta' => ['Go to your dashboard', route('dashboard')],
            ],
            'tomorrow' => [
                'subject' => "{$name} launches tomorrow",
                'preheader' => 'A quick checklist for launch day.',
                'heading' => "Tomorrow’s the day",
                'lines' => [
                    "{$name} goes live at {$liveAt}. The first few hours decide most of the ranking, so have these ready before then:",
                ],
                'list' => [
                    'Your launch post for X and LinkedIn (in your share kit)',
                    'A short message for your users, waitlist or newsletter',
                    'Where you’ll share it: Show HN, r/SideProject, Indie Hackers',
                ],
                'cta' => ['Open your launch hub', $hub],
            ],
            'live' => [
                'subject' => "{$name} is live on RankedLaunch",
                'preheader' => "Voting is open until {$closesAt}.",
                'heading' => "{$name} is live",
                'lines' => [
                    "Voting is open until {$closesAt}. Share your page now: launches that get votes early stay near the top all day.",
                    'Your share image updates with your rank, so every post you make looks right.',
                ],
                'cta' => ['See your launch', $public],
                'secondary' => ['Share kit and live stats', $hub],
            ],
            'results' => $this->resultsContent($hub),
        };
    }

    private function resultsContent(string $hub): array
    {
        $p = $this->product;
        $rank = $p->dailyRank();
        $won = $rank && $rank <= 3;

        return [
            'subject' => $won ? "{$p->name} finished #{$rank} Product of the Day" : "Your launch results for {$p->name}",
            'preheader' => number_format($p->votes_count).' upvotes, '.number_format($p->clicks_count).' visits to your site.',
            'heading' => $won ? "#{$rank} Product of the Day" : 'Your launch results',
            'lines' => array_filter([
                $won
                    ? "Congratulations. {$p->name} finished #{$rank} yesterday. Your badge now shows it. Add it to your site and share the news."
                    : "Here’s how {$p->name} did yesterday. Your page and stats stay up, and keep counting.",
                $p->is_dofollow ? null : 'Want a dofollow backlink from your page? You can upgrade any time.',
            ]),
            'stats' => [
                'Rank' => $rank ? '#'.$rank : '—',
                'Upvotes' => number_format($p->votes_count),
                'Page views' => number_format($p->views_count),
                'Site visits' => number_format($p->clicks_count),
            ],
            'cta' => [$won ? 'Get your winner badge' : 'See full results', $hub],
        ];
    }

    private function upsellLine(): ?string
    {
        if ($this->product->plan !== 'free' || ! app(\App\Services\Payments::class)->available()) {
            return null;
        }

        $pricing = app(Pricing::class);
        $price = Pricing::dollars($pricing->priceFor('pro'));
        $offer = $pricing->isPromoPlan('pro') ? " (launch offer, {$pricing->promoRemaining()} ".Str::plural('spot', $pricing->promoRemaining()).' left)' : '';

        return 'Want your launch to help your SEO too? A '.config('rankedlaunch.plans.pro.name')." adds a dofollow backlink from your page, for {$price}{$offer}, one-time.";
    }
}
