{{--
    Standalone error shell for 500 / 503. Deliberately uses no database, no session-
    dependent components and no shared layout, so it still renders when things are broken.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · RankedLaunch</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <style>
        :root { --bg: #fafafa; --surface: #fff; --ink: #171717; --ink-2: #6b6b70; --line: rgba(0,0,0,.08); color-scheme: light; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #0a0a0a; --surface: #161617; --ink: #f5f5f7; --ink-2: #98989d; --line: rgba(84,84,88,.55); color-scheme: dark; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100svh; display: grid; place-items: center; padding: 24px 16px; background: var(--bg); color: var(--ink);
               font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", Inter, "Segoe UI", Roboto, sans-serif; -webkit-font-smoothing: antialiased; }
        main { width: 100%; max-width: 440px; text-align: center; }
        .mark { width: 44px; height: 44px; display: block; margin: 0 auto 28px; color: var(--ink); }
        .code { font-size: 14px; font-weight: 600; color: var(--ink-2); font-variant-numeric: tabular-nums; margin: 0; }
        h1 { font-size: clamp(28px, 6vw, 36px); line-height: 1.1; letter-spacing: -0.035em; margin: 10px 0 0; font-weight: 700; }
        p.msg { color: var(--ink-2); line-height: 1.6; margin: 14px 0 0; }
        .actions { margin-top: 28px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        a.btn { display: inline-flex; align-items: center; height: 42px; padding: 0 18px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none; }
        a.primary { background: var(--ink); color: var(--bg); }
        a.outline { background: var(--surface); color: var(--ink); box-shadow: inset 0 0 0 1px var(--line); }
    </style>
</head>
<body>
    <main>
        <svg class="mark" viewBox="0 0 32 32" aria-hidden="true">
            <rect width="32" height="32" rx="8.5" fill="currentColor" />
            <rect x="6.75" y="15.5" width="5.5" height="9.5" rx="1.25" fill="var(--bg)" opacity=".55" />
            <rect x="13.25" y="8" width="5.5" height="17" rx="1.25" fill="var(--bg)" />
            <rect x="19.75" y="12" width="5.5" height="13" rx="1.25" fill="var(--bg)" opacity=".78" />
        </svg>
        <p class="code">{{ $code }}</p>
        <h1>{{ $title }}</h1>
        <p class="msg">{{ $message }}</p>
        <div class="actions">
            <a class="btn primary" href="/">Try again</a>
            <a class="btn outline" href="https://x.com/rankedlaunch" rel="noopener">Status on X</a>
        </div>
    </main>
</body>
</html>
