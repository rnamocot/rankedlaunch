# rankedlaunch

RankedLaunch is a daily launch board for indie makers. Products go live at 00:00 UTC and are ranked by community upvotes. Makers get a permanent page, a launch kit, stats and an embeddable badge. Paid launches (one-time) pick their date and get a dofollow backlink.

Built with Laravel 13, Blade, Tailwind CSS 4 and MySQL (SQLite locally).

## Run locally

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed      # demo data + admin account (see DatabaseSeeder)
php artisan storage:link
npm run build                   # or: npm run dev
php artisan serve               # http://127.0.0.1:8000
php artisan test                # run the test suite
```

## Configuration

All settings are documented in `.env.example`:

- **Payments:** `PAYMENT_PROVIDER=polar` (or `lemonsqueezy`) plus that provider's keys and product IDs.
- **Email:** `MAIL_*`. Launch reminders and results are sent hourly by the scheduler.
- **Social sign-in:** Google, GitHub and X client keys.
- **Launch offer and queue:** `RL_*`. These can also be changed live in Admin → Settings.

## Production (Hostinger)

Built CSS/JS (`public/build`) is committed, because shared hosting can't run Node. Run `npm run build` before every commit that changes styles or scripts.

- Point the domain at the app's `public` folder and set `APP_ENV=production` and `APP_DEBUG=false`.
- First install: `composer install --no-dev`, `php artisan key:generate`, `php artisan migrate --force`, `php artisan db:seed --force` (categories only in production), `php artisan storage:link`.
- Admin: sign up on the site, then run `php artisan rankedlaunch:make-admin you@example.com`.
- Add a cron job: `* * * * * php /path/to/artisan schedule:run`.
- Create the payment webhook: `https://rankedlaunch.com/webhooks/polar` for the `order.paid` event.
- Updates after that: `git push`, then on the server run `bash deploy.sh`.
- Admin dashboard: `/admin`. The System page shows anything that still needs setting up.
