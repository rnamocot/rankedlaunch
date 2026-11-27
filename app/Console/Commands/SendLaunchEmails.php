<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\MakerMailer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('launches:send-emails')]
#[Description('Send "launching tomorrow", "you’re live" and results emails to makers')]
class SendLaunchEmails extends Command
{
    public function handle(MakerMailer $mailer): int
    {
        $sent = 0;

        // [email type, launch date, tracking column]
        $batches = [
            ['tomorrow', today()->addDay(), 'notified_tomorrow_at'],
            ['live', today(), 'notified_live_at'],
            ['results', today()->subDay(), 'notified_results_at'],
        ];

        foreach ($batches as [$type, $date, $column]) {
            Product::with('user')
                ->where('status', 'approved')
                ->whereDate('launch_date', $date)
                ->whereNull($column)
                ->each(function (Product $product) use ($mailer, $type, $column, &$sent) {
                    // Mark first so a crash mid-run never double-sends.
                    $product->forceFill([$column => now()])->saveQuietly();

                    if ($mailer->send($product, $type)) {
                        $sent++;
                    }
                });
        }

        \Illuminate\Support\Facades\Cache::forever('rankedlaunch.scheduler_last_run', now()->toIso8601String());

        $this->info("Sent {$sent} launch ".str('email')->plural($sent).'.');

        return self::SUCCESS;
    }
}
