<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\LogoFetcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Signature('listings:fetch-logos {--all : Refetch logos that are already set}')]
#[Description('Find and store real logos for imported directory listings')]
class FetchListingLogos extends Command
{
    public function handle(LogoFetcher $fetcher): int
    {
        $products = Product::whereNotNull('source')
            ->when(! $this->option('all'), fn ($q) => $q->whereNull('logo'))
            ->get();

        foreach ($products as $product) {
            try {
                $old = $product->logo;
                $product->update(['logo' => $path = $fetcher->fetch($product)]);
                if ($old && $old !== $path && str_starts_with($old, 'logos/')) {
                    Storage::disk('public')->delete($old);
                }
                $this->line("{$product->name}: ".(str_ends_with($path, 'monogram.svg') ? 'monogram' : 'logo found'));
            } catch (Throwable $e) {
                $this->warn("{$product->name}: failed ({$e->getMessage()})");
            }
        }

        $this->info("Updated {$products->count()} ".str('listing')->plural($products->count()).'.');

        return self::SUCCESS;
    }
}
