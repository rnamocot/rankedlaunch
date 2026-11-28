<?php

namespace App\Console\Commands;

use App\Services\ShowHnImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('listings:import-show-hn {--limit= : Most listings to add} {--min-score= : Minimum HN points}')]
#[Description('List new products from Hacker News "Show HN" as directory listings (never ranked)')]
class ImportShowHn extends Command
{
    public function handle(ShowHnImporter $importer): int
    {
        $limit = (int) ($this->option('limit') ?: config('rankedlaunch.show_hn_import.per_day'));
        $minScore = (int) ($this->option('min-score') ?: config('rankedlaunch.show_hn_import.min_score'));

        ['imported' => $imported, 'skipped' => $skipped] = $importer->run($limit, $minScore);

        $this->info("Listed {$imported} new ".str('product')->plural($imported)." from Show HN ({$skipped} skipped).");

        return self::SUCCESS;
    }
}
