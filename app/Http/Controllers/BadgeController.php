<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Embeddable badges. They update themselves: "Launched on RankedLaunch" becomes
 * "#1 Product of the Day" / "#2 Product of the Week" once the results are in.
 * SVG for websites and READMEs, PNG (2x) for app stores, emails and anything without SVG.
 */
class BadgeController extends Controller
{
    public function show(Request $request, Product $product)
    {
        abort_unless($product->isLive(), 404);

        [$eyebrow, $title] = self::lines($product);
        $theme = $this->theme($request);

        $svg = view('badge', compact('eyebrow', 'title', 'theme', 'product'))->render();

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function png(Request $request, Product $product)
    {
        abort_unless($product->isLive(), 404);

        [$eyebrow, $title] = self::lines($product);
        $dark = $this->theme($request) === 'dark';

        // 2x resolution of the 220×54 badge
        $s = 2;
        $img = imagecreatetruecolor(220 * $s, 54 * $s);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));

        $bg = $dark ? [29, 29, 31] : [255, 255, 255];
        $fg = $dark ? [255, 255, 255] : [29, 29, 31];
        $sub = $dark ? [161, 161, 166] : [110, 110, 115];
        $stroke = $dark ? [58, 58, 60] : [210, 210, 215];
        $c = fn ($rgb) => imagecolorallocate($img, ...$rgb);

        $this->roundRect($img, 0, 0, 220 * $s - 1, 54 * $s - 1, 12 * $s, $c($stroke));
        $this->roundRect($img, $s, $s, 220 * $s - 1 - $s, 54 * $s - 1 - $s, 11 * $s, $c($bg));

        // Podium mark
        $x = 14 * $s;
        $y = 13 * $s;
        $this->roundRect($img, $x, $y, $x + 28 * $s, $y + 28 * $s, 7 * $s, $c($fg));
        foreach ([[5.9, 13.6, 8.3, 0.55], [11.6, 7, 14.9, 1], [17.3, 10.5, 11.4, 0.78]] as [$bx, $by, $bh, $alpha]) {
            $mix = array_map(fn ($a, $b) => (int) round($a * $alpha + $b * (1 - $alpha)), $bg, $fg);
            imagefilledrectangle($img, (int) ($x + $bx * $s), (int) ($y + $by * $s), (int) ($x + ($bx + 4.8) * $s), (int) ($y + ($by + $bh) * $s), $c($mix));
        }

        $bold = resource_path('fonts/Inter-700.ttf');
        $medium = resource_path('fonts/Inter-500.ttf');
        imagettftext($img, 7 * $s, 0, 54 * $s, 23 * $s, $c($sub), $medium, $eyebrow);
        imagettftext($img, 11.2 * $s, 0, 54 * $s, 39 * $s, $c($fg), $bold, $title);

        ob_start();
        imagepng($img);

        return response(ob_get_clean(), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /** [eyebrow, title] for the badge, based on how the launch placed. */
    public static function lines(Product $product): array
    {
        $award = $product->award();

        return $award ? ['RANKEDLAUNCH', $award['label']] : ['LAUNCHED ON', 'RankedLaunch'];
    }

    private function theme(Request $request): string
    {
        return $request->query('theme') === 'dark' ? 'dark' : 'light';
    }

    private function roundRect($img, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
            imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $color);
        }
    }
}
