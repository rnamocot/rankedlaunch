<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ShareImage;

class ShareImageController extends Controller
{
    public function product(Product $product, ShareImage $images)
    {
        abort_unless(in_array($product->status, ['pending', 'approved']), 404);

        return $this->png($images->forProduct($product), $product->slug);
    }

    public function site(ShareImage $images)
    {
        return $this->png($images->forSite(), 'rankedlaunch');
    }

    private function png(string $path, string $name)
    {
        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
            'Content-Disposition' => 'inline; filename="'.$name.'-rankedlaunch.png"',
        ]);
    }
}
