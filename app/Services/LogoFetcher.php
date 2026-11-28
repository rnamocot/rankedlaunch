<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Finds a real logo for an imported listing and stores it on the public disk:
 * the site's apple-touch-icon or largest declared icon, then Google's favicon (only if it's
 * a real one, not the generic globe), and finally a generated monogram tile.
 */
class LogoFetcher
{
    private const IMAGE_TYPES = [
        'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif',
        'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico',
    ];

    private const MAX_BYTES = 1_000_000;

    /** Store the best logo we can find and return its public-disk path. */
    public function fetch(Product $product): string
    {
        foreach ($this->candidates($product->url) as $candidate) {
            if ($path = $this->download($candidate, $product)) {
                return $path;
            }
        }

        return $this->monogram($product);
    }

    /**
     * Icon URLs to try, best first.
     *
     * @return array<int, string>
     */
    public function candidates(string $siteUrl): array
    {
        $origin = $this->origin($siteUrl);
        $declared = [];

        // A GitHub repo would only give GitHub's own icon: use the repo owner's avatar instead.
        $host = strtolower((string) parse_url($siteUrl, PHP_URL_HOST));
        if (in_array($host, ['github.com', 'www.github.com'], true)) {
            $owner = explode('/', trim((string) parse_url($siteUrl, PHP_URL_PATH), '/'))[0] ?? '';

            return $owner !== '' ? ['https://github.com/'.rawurlencode($owner).'.png?size=200'] : [];
        }

        try {
            $html = Http::timeout(6)->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; RankedLaunchBot/1.0; +https://rankedlaunch.com)'])
                ->get($siteUrl)->body();

            preg_match_all('/<link\b[^>]*>/i', Str::limit($html, 300_000, ''), $links);
            foreach ($links[0] as $tag) {
                if (! preg_match('/\brel=["\']?([^"\'>]+)/i', $tag, $rel) || ! preg_match('/\bhref=["\']?([^"\'\s>]+)/i', $tag, $href)) {
                    continue;
                }
                $rel = strtolower($rel[1]);
                if (! str_contains($rel, 'icon') || str_contains($rel, 'mask-icon')) {
                    continue;
                }
                preg_match('/\bsizes=["\']?(\d+)/i', $tag, $size);
                $score = (int) ($size[1] ?? 0) + (str_contains($rel, 'apple-touch-icon') ? 1000 : 0);
                if (str_ends_with(strtolower(parse_url($href[1], PHP_URL_PATH) ?? ''), '.svg')) {
                    continue; // never store third-party SVG
                }
                $declared[$this->absolute(html_entity_decode($href[1]), $siteUrl)] = $score;
            }
        } catch (Throwable) {
            // site unreachable: fall back to the conventional locations
        }

        arsort($declared);

        return array_values(array_unique([
            ...array_keys($declared),
            $origin.'/apple-touch-icon.png',
            'https://www.google.com/s2/favicons?sz=128&domain='.urlencode((string) parse_url($siteUrl, PHP_URL_HOST)),
        ]));
    }

    private function download(string $url, Product $product): ?string
    {
        try {
            $response = Http::timeout(6)->get($url);
        } catch (Throwable) {
            return null;
        }

        // Google answers 404 (with a globe image) when it has no favicon for the site.
        $type = strtolower(trim(Str::before((string) $response->header('Content-Type'), ';')));
        $body = $response->body();
        if (! $response->successful() || ! isset(self::IMAGE_TYPES[$type]) || strlen($body) < 100 || strlen($body) > self::MAX_BYTES) {
            return null;
        }

        // Tiny icons look blurry; skip anything we can measure as under 48px.
        // .ico files are usually 16–32px and tiny icons look blurry: require 48px or more.
        $size = @getimagesizefromstring($body);
        if (self::IMAGE_TYPES[$type] === 'ico' || ! $size || $size[0] < 48) {
            return null;
        }

        $path = 'logos/'.$product->slug.'-'.Str::random(6).'.'.self::IMAGE_TYPES[$type];
        Storage::disk('public')->put($path, $body);

        return $path;
    }

    /** A dark rounded tile with the product's first letter, matching the site's monochrome style. */
    private function monogram(Product $product): string
    {
        $letter = e(mb_strtoupper(mb_substr(preg_replace('/[^\p{L}\p{N}]/u', '', $product->name) ?: '?', 0, 1)));
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128"><rect width="128" height="128" fill="#1d1d1f"/><text x="64" y="64" dy="0.36em" text-anchor="middle" font-family="-apple-system,BlinkMacSystemFont,'Segoe UI',Inter,Arial,sans-serif" font-size="64" font-weight="700" fill="#ffffff">{$letter}</text></svg>
SVG;
        $path = 'logos/'.$product->slug.'-monogram.svg';
        Storage::disk('public')->put($path, $svg);

        return $path;
    }

    private function origin(string $url): string
    {
        return parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST);
    }

    private function absolute(string $href, string $base): string
    {
        if (Str::startsWith($href, ['http://', 'https://'])) {
            return $href;
        }
        if (Str::startsWith($href, '//')) {
            return parse_url($base, PHP_URL_SCHEME).':'.$href;
        }
        if (Str::startsWith($href, '/')) {
            return $this->origin($base).$href;
        }

        $path = parse_url($base, PHP_URL_PATH) ?: '/';
        $dir = str_ends_with($path, '/') ? rtrim($path, '/') : rtrim(Str::beforeLast($path, '/'), '/');

        return $this->origin($base).$dir.'/'.ltrim($href, './');
    }
}
