<?php

namespace App\Services;

use App\Models\Product;
use GdImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Builds 1200×630 PNG share images (Open Graph / X cards) with GD.
 * Drawn at 2× and downsampled so shapes are anti-aliased. Cached on the public disk.
 */
class ShareImage
{
    private const W = 1200;
    private const H = 630;
    private const S = 2; // supersampling factor

    private string $bold;
    private string $medium;

    public function __construct()
    {
        $this->bold = resource_path('fonts/Inter-700.ttf');
        $this->medium = resource_path('fonts/Inter-500.ttf');
    }

    /** Returns the cached file path for a product, generating it when the content changed. */
    public function forProduct(Product $product): string
    {
        [$eyebrow, $footer] = $this->productLines($product);

        $version = md5(implode('|', [$product->name, $product->tagline, $product->logo, $eyebrow, $footer, 'v2']));
        $path = "og/{$product->slug}-{$version}.png";

        return $this->cached($path, fn () => $this->render(
            title: $product->name,
            subtitle: $product->tagline,
            eyebrow: $eyebrow,
            footer: $footer,
            logo: $this->loadLogo($product),
        ));
    }

    public function forSite(): string
    {
        return $this->cached('og/site-v2.png', fn () => $this->render(
            title: 'Launch your product.',
            subtitle: 'Climb the ranks. A daily launch board for indie makers.',
            eyebrow: null,
            footer: 'rankedlaunch.com',
            logo: null,
        ));
    }

    private function productLines(Product $product): array
    {
        if (! $product->isLive()) {
            return [null, $product->launch_date ? 'Launching '.$product->launch_date->format('M j, Y').' on RankedLaunch' : 'Coming soon to RankedLaunch'];
        }

        $daily = $product->dailyRank();
        $weekly = $product->weeklyRank();
        $weekOver = $product->launch_date->copy()->endOfWeek()->isPast();
        $dayOver = $product->launch_date->lt(today());

        $eyebrow = match (true) {
            $weekOver && $weekly <= 3 => "#{$weekly} Product of the Week",
            $dayOver && $daily <= 3 => "#{$daily} Product of the Day",
            ! $dayOver => 'Launching today',
            default => null,
        };

        $votes = number_format($product->votes_count).' '.Str::plural('upvote', $product->votes_count);

        return [$eyebrow, 'Launched '.$product->launch_date->format('M j, Y').'  ·  '.$votes];
    }

    private function cached(string $path, callable $make): string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            // Remove older versions for the same subject.
            $prefix = Str::beforeLast($path, '-');
            foreach ($disk->files('og') as $old) {
                if (Str::startsWith($old, $prefix.'-')) {
                    $disk->delete($old);
                }
            }
            $disk->put($path, $make());
        }

        return $disk->path($path);
    }

    private function render(string $title, string $subtitle, ?string $eyebrow, string $footer, ?GdImage $logo): string
    {
        $s = self::S;
        $img = imagecreatetruecolor(self::W * $s, self::H * $s);
        imagesavealpha($img, true);

        $bg = $this->color($img, '#fafafa');
        $ink = $this->color($img, '#171717');
        $ink2 = $this->color($img, '#6b6b70');
        $ink3 = $this->color($img, '#a3a3a8');
        $line = $this->color($img, '#e6e6e8');
        $white = $this->color($img, '#ffffff');
        $tray = $this->color($img, '#f0f0f1');

        imagefill($img, 0, 0, $bg);

        // Tray + white panel, like the site's layout
        $this->roundedRect($img, 40, 40, self::W - 40, self::H - 40, 36, $tray);
        $this->roundedRect($img, 52, 52, self::W - 52, self::H - 52, 28, $white);
        $this->roundedRectOutline($img, 52, 52, self::W - 52, self::H - 52, 28, $line);

        $x = 112;

        // Brand: podium mark + wordmark (top left)
        $this->brandMark($img, $x, 104, 44, $ink, $white);
        $this->text($img, 'RankedLaunch', $this->bold, 26, $x + 60, 136, $ink);

        // Product logo
        $titleX = $x;
        if ($logo) {
            $size = 168;
            $this->pasteRounded($img, $logo, $x, 222, $size, 38, $line);
            $titleX = $x + $size + 48;
        }

        // Eyebrow (rank or status)
        $y = 262;
        if ($eyebrow) {
            $this->text($img, mb_strtoupper($eyebrow), $this->bold, 20, $titleX, $y, $ink2, 0.08);
            $y += 64;
        } else {
            $y += 40;
        }

        // Title + subtitle, fitted to the space left of/under the logo
        $maxWidth = self::W - 112 - $titleX;
        $titleSize = $this->fitSize($title, $this->bold, 72, 44, $maxWidth);
        $this->text($img, $title, $this->bold, $titleSize, $titleX, $y + 14, $ink);

        $lines = $this->wrap($subtitle, $this->medium, 30, $maxWidth, 2);
        $sy = $y + 72;
        foreach ($lines as $l) {
            $this->text($img, $l, $this->medium, 30, $titleX, $sy, $ink2);
            $sy += 44;
        }

        // Footer
        imagefilledrectangle($img, $x * $s, 488 * $s, (self::W - 112) * $s, 489 * $s, $line);
        $this->text($img, $footer, $this->medium, 24, $x, 540, $ink2);
        $cta = 'rankedlaunch.com';
        if ($footer !== $cta) {
            $box = imagettfbbox(24 * $s * 0.75, 0, $this->medium, $cta);
            $this->text($img, $cta, $this->medium, 24, (int) (self::W - 112 - ($box[2] - $box[0]) / $s), 540, $ink3);
        }

        // Downsample to final size for smooth edges
        $out = imagecreatetruecolor(self::W, self::H);
        imagecopyresampled($out, $img, 0, 0, 0, 0, self::W, self::H, self::W * $s, self::H * $s);

        ob_start();
        imagepng($out, null, 6);

        return ob_get_clean();
    }

    /* ---------- drawing helpers (coordinates in 1× units) ---------- */

    private function color(GdImage $img, string $hex): int
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return imagecolorallocate($img, $r, $g, $b);
    }

    private function roundedRect(GdImage $img, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        $s = self::S;
        [$x1, $y1, $x2, $y2, $r] = [$x1 * $s, $y1 * $s, $x2 * $s, $y2 * $s, $r * $s];
        imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
            imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $color);
        }
    }

    private function roundedRectOutline(GdImage $img, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        $s = self::S;
        [$x1, $y1, $x2, $y2, $r] = [$x1 * $s, $y1 * $s, $x2 * $s, $y2 * $s, $r * $s];
        imagesetthickness($img, $s);
        imageline($img, $x1 + $r, $y1, $x2 - $r, $y1, $color);
        imageline($img, $x1 + $r, $y2, $x2 - $r, $y2, $color);
        imageline($img, $x1, $y1 + $r, $x1, $y2 - $r, $color);
        imageline($img, $x2, $y1 + $r, $x2, $y2 - $r, $color);
        imagearc($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, 180, 270, $color);
        imagearc($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, 270, 360, $color);
        imagearc($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, 90, 180, $color);
        imagearc($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, 0, 90, $color);
        imagesetthickness($img, 1);
    }

    private function brandMark(GdImage $img, int $x, int $y, int $size, int $ink, int $white): void
    {
        $u = $size / 32; // logo is designed on a 32-unit grid
        $this->roundedRect($img, $x, $y, $x + $size, $y + $size, (int) round(8.5 * $u), $ink);
        foreach ([[6.75, 15.5, 9.5], [13.25, 8, 17], [19.75, 12, 13]] as [$bx, $by, $bh]) {
            $this->roundedRect($img, (int) round($x + $bx * $u), (int) round($y + $by * $u), (int) round($x + ($bx + 5.5) * $u), (int) round($y + ($by + $bh) * $u), (int) max(1, round(1.25 * $u)), $white);
        }
    }

    private function text(GdImage $img, string $text, string $font, int $size, int $x, int $y, int $color, float $tracking = 0): void
    {
        $s = self::S;
        $pt = $size * $s * 0.75; // GD uses points

        if ($tracking == 0) {
            imagettftext($img, $pt, 0, $x * $s, $y * $s, $color, $font, $text);

            return;
        }

        // Manual letter-spacing
        $cx = $x * $s;
        foreach (mb_str_split($text) as $char) {
            imagettftext($img, $pt, 0, (int) $cx, $y * $s, $color, $font, $char);
            $box = imagettfbbox($pt, 0, $font, $char);
            $advance = $char === ' ' ? $pt * 0.3 : ($box[2] - $box[0]);
            $cx += $advance + $tracking * $size * $s + ($char === ' ' ? 0 : $pt * 0.06);
        }
    }

    private function textWidth(string $text, string $font, int $size): float
    {
        $box = imagettfbbox($size * 0.75, 0, $font, $text);

        return $box[2] - $box[0];
    }

    private function fitSize(string $text, string $font, int $max, int $min, int $width): int
    {
        for ($size = $max; $size > $min; $size -= 2) {
            if ($this->textWidth($text, $font, $size) <= $width) {
                return $size;
            }
        }

        return $min;
    }

    private function wrap(string $text, string $font, int $size, int $width, int $maxLines): array
    {
        $lines = [];
        $current = '';
        foreach (preg_split('/\s+/', trim($text)) as $word) {
            $try = $current === '' ? $word : "$current $word";
            if ($this->textWidth($try, $font, $size) <= $width) {
                $current = $try;
            } else {
                $lines[] = $current;
                $current = $word;
            }
        }
        $lines[] = $current;

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[$maxLines - 1] = rtrim($lines[$maxLines - 1], '.,;: ').'…';
        }

        return array_filter($lines, fn ($l) => $l !== '');
    }

    private function pasteRounded(GdImage $img, GdImage $logo, int $x, int $y, int $size, int $radius, int $border): void
    {
        $s = self::S;
        $px = $size * $s;
        $r = $radius * $s;

        $tile = imagecreatetruecolor($px, $px);
        imagefill($tile, 0, 0, imagecolorallocate($tile, 255, 255, 255));
        imagecopyresampled($tile, $logo, 0, 0, 0, 0, $px, $px, imagesx($logo), imagesy($logo));

        // Copy only pixels inside the rounded square
        $ox = $x * $s;
        $oy = $y * $s;
        for ($i = 0; $i < $px; $i++) {
            for ($j = 0; $j < $px; $j++) {
                $dx = max($r - $i, 0, $i - ($px - 1 - $r));
                $dy = max($r - $j, 0, $j - ($px - 1 - $r));
                if ($dx * $dx + $dy * $dy <= $r * $r) {
                    imagesetpixel($img, $ox + $i, $oy + $j, imagecolorat($tile, $i, $j));
                }
            }
        }

        $this->roundedRectOutline($img, $x, $y, $x + $size, $y + $size, $radius, $border);
    }

    private function loadLogo(Product $product): ?GdImage
    {
        try {
            if ($product->logo && ! Str::startsWith($product->logo, 'http')) {
                $bytes = Storage::disk('public')->get($product->logo);
            } else {
                $bytes = Http::timeout(5)->get($product->logoUrl())->throw()->body();
            }

            $image = @imagecreatefromstring($bytes);

            return $image ?: null;
        } catch (Throwable) {
            return null;
        }
    }
}
