<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Lists new products that makers share on Hacker News "Show HN", via the official public API.
 * Each becomes a directory listing (owned by the house account, linked back to the HN post),
 * shown in Discover and its category but never in rankings or awards.
 */
class ShowHnImporter
{
    public function __construct(private LogoFetcher $logos) {}

    private const API = 'https://hacker-news.firebaseio.com/v0';

    /** Hosts that are posts or repos about a product rather than the product's own site. */
    private const SKIP_HOSTS = ['news.ycombinator.com', 'youtube.com', 'youtu.be', 'twitter.com', 'x.com', 'medium.com', 'reddit.com'];

    /** Whole-word keyword => category slug, first match wins. */
    private const CATEGORY_KEYWORDS = [
        'agent' => 'ai-agents', 'agents' => 'ai-agents', 'llm' => 'ai-tools', 'llms' => 'ai-tools', 'gpt' => 'ai-tools', 'ai' => 'ai-tools', 'chatbot' => 'ai-assistants-chatbots',
        'api' => 'apis-sdks', 'sdk' => 'apis-sdks', 'database' => 'databases-backend', 'postgres' => 'databases-backend', 'sqlite' => 'databases-backend', 'sql' => 'databases-backend',
        'cli' => 'developer-tools', 'terminal' => 'developer-tools', 'compiler' => 'developer-tools', 'debugger' => 'developer-tools', 'simulator' => 'developer-tools', 'simulators' => 'developer-tools',
        'deploy' => 'devops-hosting', 'kubernetes' => 'devops-hosting', 'hosting' => 'devops-hosting', 'vm' => 'devops-hosting', 'vms' => 'devops-hosting', 'monitoring' => 'monitoring-logging', 'logs' => 'monitoring-logging',
        'security' => 'security', 'privacy' => 'security', 'encrypted' => 'security', 'editor' => 'code-editors-ides', 'vim' => 'code-editors-ides', 'ide' => 'code-editors-ides',
        'notes' => 'notes-docs', 'markdown' => 'notes-docs', 'docs' => 'notes-docs', 'todo' => 'task-project-management', 'tasks' => 'task-project-management', 'calendar' => 'calendar-scheduling',
        'chat' => 'communication-chat', 'automation' => 'automation-workflows', 'workflow' => 'automation-workflows', 'workflows' => 'automation-workflows', 'seo' => 'seo', 'newsletter' => 'email-marketing',
        'design' => 'design', 'figma' => 'design', 'no-code' => 'no-code', 'nocode' => 'no-code', 'analytics' => 'analytics', 'dashboard' => 'analytics',
        'invoice' => 'payments-invoicing', 'invoices' => 'payments-invoicing', 'payments' => 'payments-invoicing', 'finance' => 'finance', 'budget' => 'personal-finance', 'store' => 'e-commerce',
        'forms' => 'forms-surveys', 'survey' => 'forms-surveys', 'learn' => 'education-learning', 'learning' => 'education-learning', 'math' => 'education-learning', 'course' => 'education-learning',
        'health' => 'health-fitness', 'fitness' => 'health-fitness', 'music' => 'music-audio', 'synth' => 'music-audio', 'audio' => 'music-audio', 'game' => 'games', 'puzzle' => 'games',
        'rss' => 'news-reading', 'reader' => 'news-reading', 'extension' => 'browser-extensions', 'chrome' => 'browser-extensions', 'macos' => 'mac-apps', 'mac' => 'mac-apps',
        'ios' => 'ios-apps', 'iphone' => 'ios-apps', 'android' => 'android-apps', 'crypto' => 'crypto-web3', 'hardware' => 'hardware', 'video' => 'video-editing', 'photo' => 'photo-editing',
        'open source' => 'open-source', 'open-source' => 'open-source', 'productivity' => 'productivity',
    ];

    /** Static hosts where the subdomain is a username, so the name comes from the path. */
    private const USER_HOSTS = ['github.io', 'gitlab.io', 'vercel.app', 'netlify.app', 'pages.dev', 'surge.sh'];

    /**
     * Import up to $limit new Show HN products with at least $minScore points.
     *
     * @return array{imported: int, skipped: int}
     */
    public function run(int $limit, int $minScore): array
    {
        $imported = 0;
        $skipped = 0;

        $ids = Http::timeout(10)->retry(2, 500)->get(self::API.'/showstories.json')->throw()->json() ?? [];
        $known = Product::where('source', 'hn')->whereIn('source_id', array_map('strval', $ids))->pluck('source_id')->flip();

        foreach ($ids as $id) {
            if ($imported >= $limit) {
                break;
            }
            if (isset($known[(string) $id])) {
                continue;
            }

            try {
                $item = Http::timeout(8)->get(self::API."/item/{$id}.json")->json();
                $product = $item ? $this->import($item, $minScore) : null;
                $product ? $imported++ : $skipped++;
            } catch (Throwable $e) {
                $skipped++;
                Log::warning('Show HN import skipped an item', ['id' => $id, 'error' => $e->getMessage()]);
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * Turn one HN item into a listing, or null if it doesn't qualify.
     *
     * @param  array<string, mixed>  $item
     */
    public function import(array $item, int $minScore): ?Product
    {
        $url = $item['url'] ?? null;
        $title = trim(html_entity_decode((string) ($item['title'] ?? ''), ENT_QUOTES));

        if (($item['type'] ?? null) !== 'story' || ! empty($item['dead']) || ! empty($item['deleted'])
            || ! $url || ! filter_var($url, FILTER_VALIDATE_URL) || ($item['score'] ?? 0) < $minScore
            || ! Str::startsWith(Str::lower($title), 'show hn')) {
            return null;
        }

        $host = Str::of(parse_url($url, PHP_URL_HOST) ?? '')->lower()->replaceStart('www.', '')->toString();
        if ($host === '' || in_array($host, self::SKIP_HOSTS, true)) {
            return null;
        }

        // Already listed or launched here (same site): skip. GitHub repos are compared by full URL.
        $sameSite = $host === 'github.com'
            ? Product::where('url', rtrim($url, '/'))->exists()
            : Product::where('url', 'like', '%://'.$host.'%')->orWhere('url', 'like', '%://www.'.$host.'%')->exists();
        if ($sameSite) {
            return null;
        }

        // Products only: no blog posts, write-ups or personal stories ("I restored…", "My year of…").
        $path = (string) parse_url($url, PHP_URL_PATH);
        $rest = trim(preg_replace('/^show hn\s*[:\-–—]?\s*/i', '', $title));
        if (preg_match('#/(blog|posts?|articles?|story|stories|news|writing|notes)/|/\d{4}/\d{1,2}/#i', $path)
            || preg_match('/^(i|i\'ve|i\'m|we|we\'ve|my|our)\s/i', $rest)) {
            return null;
        }

        $body = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(str_replace(['<p>', '<br>'], "\n\n", (string) ($item['text'] ?? ''))), ENT_QUOTES)));
        $author = Str::limit((string) ($item['by'] ?? ''), 60, '');

        [$name, $tagline] = $this->splitTitle($title, $url, Str::before($body, '. '));
        if ($name === '') {
            return null;
        }
        $description = trim(($body !== '' ? Str::limit($body, 1200)."\n\n" : $tagline."\n\n")
            .'Shared on Hacker News “Show HN”'.($author ? " by {$author}" : '').'.');

        $product = Product::create([
            'user_id' => User::listingsAccount()->id,
            'category_id' => $this->guessCategory($title) ?? $this->guessCategory($body) ?? Category::where('slug', 'other')->value('id'),
            'name' => $name,
            'slug' => Product::uniqueSlug($name),
            'url' => rtrim($url, '/'),
            'tagline' => Str::limit($tagline, 117),
            'description' => $description,
            'pricing_type' => 'free',
            'status' => 'approved',
            'launch_date' => today(),
            'source' => 'hn',
            'source_id' => (string) $item['id'],
            'source_url' => 'https://news.ycombinator.com/item?id='.$item['id'],
            'source_author' => $author ?: null,
        ]);

        try {
            $product->update(['logo' => $this->logos->fetch($product)]);
        } catch (Throwable $e) {
            Log::warning('Logo fetch failed', ['product' => $product->id, 'error' => $e->getMessage()]);
        }

        return $product;
    }

    /**
     * "Show HN: Acme – Fast invoices for freelancers" => ["Acme", "Fast invoices for freelancers"].
     * A short title with no separator is the name itself; the tagline then comes from the post text.
     *
     * @return array{0: string, 1: string}
     */
    public function splitTitle(string $title, string $url, string $firstSentence = ''): array
    {
        $rest = trim(preg_replace('/^show hn\s*[:\-–—]?\s*/i', '', $title));
        $parts = preg_split('/\s+[–—-]\s+|:\s+|,\s+(?=an?\s|the\s)/u', $rest, 2);
        $firstSentence = trim($firstSentence);
        $usable = $firstSentence !== '' && mb_strlen($firstSentence) <= 117 && ! preg_match('/^(i|we|my|our|hi|hey|hello)/i', $firstSentence);
        $fallbackTagline = $usable ? $firstSentence : 'A new project shared on Show HN';

        if (count($parts) === 2 && mb_strlen($parts[0]) <= 40) {
            [$name, $tagline] = $parts;
        } elseif (mb_strlen($rest) <= 40 && str_word_count($rest) <= 5) {
            [$name, $tagline] = [$rest, $fallbackTagline];
        } else {
            [$name, $tagline] = [$this->nameFromUrl($url), $rest];
        }

        $name = Str::limit(trim($name, " \t\"'"), 60, '');

        $tagline = trim($tagline);

        // First-person openers ("I wanted to…") read as a story, not a tagline.
        if ($tagline === '' || preg_match('/^(i|we|my|our|hi|hey|hello)\b/i', $tagline)) {
            $tagline = $usable ? $firstSentence : 'A new project shared on Show HN';
        }

        return [$name, Str::ucfirst($tagline)];
    }

    /** "acme.com" => "Acme"; "someone.github.io/sim-drop" or "github.com/someone/sim-drop" => "Sim Drop". */
    private function nameFromUrl(string $url): string
    {
        $host = Str::of(parse_url($url, PHP_URL_HOST) ?? '')->lower()->replaceStart('www.', '')->toString();
        $segments = array_values(array_filter(explode('/', (string) parse_url($url, PHP_URL_PATH))));
        $fromPath = match (true) {
            $host === 'github.com' || $host === 'gitlab.com' => $segments[1] ?? null,
            Str::endsWith($host, self::USER_HOSTS) => $segments[0] ?? null,
            default => null,
        };

        return Str::of($fromPath ?? Str::before($host, '.'))->replace(['-', '_'], ' ')->title()->toString();
    }

    private function guessCategory(string $text): ?int
    {
        foreach (self::CATEGORY_KEYWORDS as $keyword => $slug) {
            if (preg_match('/(?<![\w-])'.preg_quote($keyword, '/').'(?![\w-])/i', $text)) {
                return Category::where('slug', $slug)->value('id');
            }
        }

        return null;
    }
}
