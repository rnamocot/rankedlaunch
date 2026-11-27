<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Payments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Health checks for everything the site depends on, with fixes in plain words. */
class SystemController extends Controller
{
    public function index(Payments $payments)
    {
        $gateway = $payments->gateway();
        $lastRun = Cache::get('rankedlaunch.scheduler_last_run');
        $hoursSince = $lastRun ? now()->diffInHours($lastRun) : null;
        $production = app()->isProduction();

        $checks = [
            // [label, ok?, detail, how to fix]
            ['Scheduled tasks (cron)', $lastRun && $hoursSince < 2,
                $lastRun ? 'Last run '.now()->parse($lastRun)->diffForHumans() : 'Never run',
                'In hPanel → Cron Jobs add an hourly job (minute 0): php '.base_path('artisan').' launches:send-emails'],
            ['Email delivery', config('mail.default') !== 'log',
                'Mailer: '.config('mail.default').', from '.config('mail.from.address'),
                'Set MAIL_MAILER, MAIL_HOST, MAIL_USERNAME and MAIL_PASSWORD in .env (e.g. Resend or Hostinger SMTP).'],
            ['Payments', $gateway->configured(),
                ucfirst($gateway->name()).($gateway->configured() ? ' connected' : ' keys missing'),
                'Add your '.ucfirst($gateway->name()).' keys to .env. See .env.example.'],
            ['Payment webhook secret', filled(config('rankedlaunch.'.$gateway->name().'.webhook_secret')),
                'Webhook URL: '.route('webhooks.'.$gateway->name()),
                'Create a webhook in your payment dashboard pointing to the URL above and copy its secret into .env.'],
            ['Debug mode off', ! config('app.debug') || ! $production,
                'APP_DEBUG='.(config('app.debug') ? 'true' : 'false').', APP_ENV='.app()->environment(),
                'Set APP_DEBUG=false on the live site so errors never show code to visitors.'],
            ['HTTPS', ! $production || str_starts_with(config('app.url'), 'https://'),
                config('app.url'),
                'Set APP_URL to https://rankedlaunch.com and enable SSL in hPanel.'],
            ['Uploads folder linked', is_link(public_path('storage')) || is_dir(public_path('storage')),
                'public/storage',
                'Run: php artisan storage:link'],
            ['Storage writable', is_writable(storage_path('app/public')) && is_writable(storage_path('logs')),
                'storage/',
                'Give the web server write access to the storage folder.'],
            ['Database', $this->dbOk(), config('database.default'),
                'Check DB_ settings in .env.'],
        ];

        return view('admin.system', [
            'checks' => $checks,
            'info' => [
                'PHP' => PHP_VERSION,
                'Laravel' => app()->version(),
                'Environment' => app()->environment(),
                'Cache' => config('cache.default'),
                'Payment provider' => $gateway->name(),
                'Free disk space' => $this->disk(),
            ],
            'logErrors' => $this->recentErrors(),
        ]);
    }

    public function runScheduler(Request $request)
    {
        Artisan::call('launches:send-emails');
        ActivityLog::record('system', 'Ran launch emails manually: '.trim(Artisan::output()), user: $request->user());

        return back()->with('status', trim(Artisan::output()));
    }

    public function testEmail(Request $request)
    {
        try {
            Mail::raw('This is a test email from your RankedLaunch admin. If you can read this, email delivery works.', function ($m) use ($request) {
                $m->to($request->user()->email)->subject('RankedLaunch test email');
            });

            return back()->with('status', 'Test email sent to '.$request->user()->email.(config('mail.default') === 'log' ? ' (written to the log, mailer is “log”).' : '.'));
        } catch (Throwable $e) {
            return back()->with('status', 'Email failed: '.str($e->getMessage())->limit(150));
        }
    }

    public function clearCache(Request $request)
    {
        Artisan::call('optimize:clear');
        ActivityLog::record('system', 'Cleared caches', user: $request->user());

        return back()->with('status', 'Caches cleared.');
    }

    private function dbOk(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function disk(): string
    {
        $free = @disk_free_space(base_path());

        return $free ? round($free / 1024 / 1024 / 1024, 1).' GB' : 'unknown';
    }

    /** Last few ERROR lines from the Laravel log. */
    private function recentErrors(): array
    {
        $file = storage_path('logs/laravel.log');
        if (! is_file($file)) {
            return [];
        }

        $handle = fopen($file, 'r');
        fseek($handle, max(0, filesize($file) - 200_000));
        $tail = stream_get_contents($handle);
        fclose($handle);

        preg_match_all('/^\[([\d\-: ]+)\] \w+\.ERROR: (.{0,220})/m', $tail, $m, PREG_SET_ORDER);

        return array_reverse(array_slice(array_map(fn ($x) => ['at' => $x[1], 'message' => $x[2]], $m), -8));
    }
}
