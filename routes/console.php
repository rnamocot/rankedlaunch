<?php

use Illuminate\Support\Facades\Schedule;

// Launch emails: "tomorrow", "you're live" and results. Runs hourly so products
// approved during the day still get their email; each one is only sent once.
Schedule::command('launches:send-emails')->hourly()->withoutOverlapping();
