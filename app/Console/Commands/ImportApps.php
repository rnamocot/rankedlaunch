<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Import apps from a JSON file under an existing account, approved and scheduled.
 *   php artisan rankedlaunch:import-apps you@example.com
 *   php artisan rankedlaunch:import-apps you@example.com --file=database/data/founder-apps.json
 *
 * Each item: name, url, tagline, category (name), pricing (free|freemium|paid),
 * logo (URL), screenshots (URLs), launch_day (0 = today, 1 = tomorrow, …).
 * Apps that already exist (same slug) are skipped, so it's safe to run twice.
 */
#[Signature('rankedlaunch:import-apps {email : Owner account (must already exist)} {--file=database/data/founder-apps.json}')]
#[Description('Import apps from a JSON file under an existing account')]
class ImportApps extends Command
{
    public function handle(): int
    {
        $owner = User::where('email', $this->argument('email'))->first();
        if (! $owner) {
            $this->error('No account with that email. Sign up on the site first.');

            return self::FAILURE;
        }

        $path = base_path($this->option('file'));
        $items = is_file($path) ? json_decode(file_get_contents($path), true) : null;
        if (! is_array($items)) {
            $this->error("Couldn’t read a JSON list from {$path}.");

            return self::FAILURE;
        }

        $created = 0;
        foreach ($items as $item) {
            $slug = str($item['name'])->slug()->toString();

            if (Product::where('slug', $slug)->exists()) {
                $this->line("  skip  {$item['name']} (already listed)");
                continue;
            }

            $category = Category::where('name', $item['category'])->first();
            if (! $category) {
                $this->warn("  skip  {$item['name']}: unknown category “{$item['category']}”");
                continue;
            }

            $product = Product::create([
                'user_id' => $owner->id,
                'category_id' => $category->id,
                'name' => $item['name'],
                'slug' => $slug,
                'url' => $item['url'],
                'tagline' => $item['tagline'],
                'description' => $item['description'] ?? $item['tagline'],
                'logo' => $item['logo'] ?? null,
                'screenshots' => $item['screenshots'] ?? [],
                'pricing_type' => $item['pricing'] ?? 'freemium',
                'plan' => 'free',
                'status' => 'approved',
                'launch_date' => today()->addDays((int) ($item['launch_day'] ?? 0)),
                'is_dofollow' => true,
            ]);

            ActivityLog::record('submission', "Imported {$product->name} (launches {$product->launch_date->format('M j')})", $product);
            $this->info("  added {$product->name} · {$category->name} · launches {$product->launch_date->format('D M j')}");
            $created++;
        }

        $this->newLine();
        $this->info("Imported {$created} ".str('app')->plural($created).' for '.$owner->email.'.');

        return self::SUCCESS;
    }
}
