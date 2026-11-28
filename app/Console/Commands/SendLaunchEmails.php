<?php

namespace App\Console\Commands;

use App\Models\LaunchReminder;
use App\Models\Product;
use App\Notifications\AwardWon;
use App\Notifications\LaunchIsLive;
use App\Services\MakerMailer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

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
                ->whereNull('source')
                ->whereDate('launch_date', $date)
                ->whereNull($column)
                ->each(function (Product $product) use ($mailer, $type, $column, &$sent) {
                    // Mark first so a crash mid-run never double-sends.
                    $product->forceFill([$column => now()])->saveQuietly();

                    if ($mailer->send($product, $type)) {
                        $sent++;
                    }

                    if ($type === 'results' && $product->user && ($award = $product->award())) {
                        $product->user->notify(new AwardWon($product, $award['label']));
                    }
                });
        }

        $reminded = $this->sendLaunchReminders();

        // The host's cron only runs this command, so the daily Show HN import rides along once per UTC day.
        if (config('rankedlaunch.show_hn_import.enabled') && Cache::add('show-hn-import:'.today()->toDateString(), true, now()->addDay())) {
            try {
                $this->call('listings:import-show-hn');
            } catch (Throwable $e) {
                Log::error('Show HN import failed', ['error' => $e->getMessage()]);
            }
        }

        Cache::forever('rankedlaunch.scheduler_last_run', now()->toIso8601String());

        $this->info("Sent {$sent} launch ".str('email')->plural($sent).", {$reminded} launch ".str('reminder')->plural($reminded).'.');

        return self::SUCCESS;
    }

    /** "Notify me" subscribers hear about launches that went live today. */
    private function sendLaunchReminders(): int
    {
        $count = 0;

        LaunchReminder::with('user', 'product')
            ->whereNull('notified_at')
            ->whereHas('product', fn ($q) => $q->live()->whereDate('launch_date', today()))
            ->each(function (LaunchReminder $reminder) use (&$count) {
                // Mark first so a crash mid-run never double-sends.
                $reminder->forceFill(['notified_at' => now()])->saveQuietly();

                if (! $reminder->user || $reminder->user->banned_at) {
                    return;
                }

                try {
                    $reminder->user->notify(new LaunchIsLive($reminder->product));
                    $count++;
                } catch (Throwable $e) {
                    Log::error('Launch reminder failed', ['reminder' => $reminder->id, 'error' => $e->getMessage()]);
                }
            });

        return $count;
    }
}
