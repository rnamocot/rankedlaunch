document.documentElement.classList.add('js');

// Scroll reveal: [data-reveal] elements fade up once when they enter the viewport.
const revealables = document.querySelectorAll('[data-reveal]');
if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(
        (entries) => entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        }),
        { rootMargin: '0px 0px -8% 0px', threshold: 0.08 },
    );
    revealables.forEach((el) => {
        el.querySelectorAll('.list-row').forEach((row, i) => row.style.setProperty('--row-i', i));
        observer.observe(el);
    });
} else {
    revealables.forEach((el) => el.classList.add('is-visible'));
}

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

// Upvote toggle. Every vote button for the same product updates together.
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-vote]');
    if (!button) return;

    event.preventDefault();

    if (button.dataset.login) {
        window.location.href = button.dataset.login;
        return;
    }

    if (button.dataset.busy) return;
    button.dataset.busy = '1';

    const slug = button.dataset.vote;
    const siblings = document.querySelectorAll(`[data-vote="${CSS.escape(slug)}"]`);

    try {
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
        });

        if (response.status === 401) {
            window.location.href = '/login';
            return;
        }
        if (!response.ok) return;

        const { voted, count } = await response.json();
        siblings.forEach((el) => {
            el.setAttribute('aria-pressed', voted ? 'true' : 'false');
            el.classList.remove('is-bumped');
            void el.offsetWidth; // restart the animation
            el.classList.add('is-bumped');
            const counter = el.querySelector('[data-count]');
            if (counter) counter.textContent = count;
        });
    } finally {
        delete button.dataset.busy;
    }
});

// Copy-to-clipboard buttons
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) return;

    const source = document.getElementById(button.dataset.copy);
    if (!source) return;

    try {
        await navigator.clipboard.writeText(source.value ?? source.textContent);
        const label = button.textContent;
        button.textContent = 'Copied';
        setTimeout(() => (button.textContent = label), 1500);
    } catch {
        source.select?.();
    }
});

// Mobile menu
document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-menu-toggle]');
    if (!toggle) return;

    const menu = document.getElementById(toggle.dataset.menuToggle);
    const open = menu.hasAttribute('hidden');
    menu.toggleAttribute('hidden', !open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
});

// Go-live step: "list it now" or a date (free for every plan); full days depend on the plan.
document.querySelectorAll('[data-plan-form]').forEach((form) => {
    const dates = JSON.parse(form.dataset.dates || '{}');

    const sync = () => {
        const plan = form.querySelector('input[name="plan"]:checked')?.value || 'free';
        const when = form.querySelector('input[name="when"]:checked')?.value || 'now';
        const picker = form.querySelector('[data-date-picker]');
        const submit = form.querySelector('[data-plan-submit]');
        const left = dates[plan] || {};

        if (picker) picker.hidden = when !== 'date';
        if (submit) submit.textContent = submit.dataset[plan] ?? submit.textContent;

        form.querySelectorAll('input[name="launch_date"]').forEach((input) => {
            const spots = left[input.value] ?? 0;
            const label = input.closest('[data-date]');
            const note = label.querySelector('[data-date-note]');
            input.disabled = spots <= 0;
            if (input.disabled && input.checked) input.checked = false;
            label.classList.toggle('opacity-35', spots <= 0);
            label.classList.toggle('cursor-not-allowed', spots <= 0);
            label.classList.toggle('cursor-pointer', spots > 0);
            note.textContent = spots <= 0 ? 'Full' : spots <= 3 ? `${spots} left` : '';
        });
    };

    form.addEventListener('change', sync);
    sync();
});

// Image preview for file inputs
document.querySelectorAll('[data-preview]').forEach((input) => {
    input.addEventListener('change', () => {
        const target = document.getElementById(input.dataset.preview);
        const file = input.files?.[0];
        if (target && file) target.src = URL.createObjectURL(file);
    });
});

// Countdowns: <el data-countdown="ISO date">. Shows "2d 04:12:09" or "04:12:09".
const pad = (n) => String(n).padStart(2, '0');
const countdowns = document.querySelectorAll('[data-countdown]');
if (countdowns.length) {
    const tick = () => {
        countdowns.forEach((el) => {
            let s = Math.max(0, Math.floor((new Date(el.dataset.countdown) - Date.now()) / 1000));
            const d = Math.floor(s / 86400);
            s %= 86400;
            const hms = `${pad(Math.floor(s / 3600))}:${pad(Math.floor((s % 3600) / 60))}:${pad(s % 60)}`;
            el.textContent = d > 0 ? `${d}d ${hms}` : hms;
        });
    };
    tick();
    setInterval(tick, 1000);
}

// Count click-throughs to a product's website without changing the link itself
// (a redirect would strip the dofollow backlink value).
document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-track-click]');
    if (!link || !navigator.sendBeacon) return;
    navigator.sendBeacon(link.dataset.trackClick);
});

// Admin charts: one tooltip per chart, follows the hovered day.
document.querySelectorAll('[data-chart]').forEach((chart) => {
    const tip = chart.querySelector('[data-chart-tip]');
    const box = tip?.parentElement;
    if (!tip || !box) return;

    chart.addEventListener('mousemove', (event) => {
        const target = event.target.closest('[data-tip]');
        if (!target) {
            tip.hidden = true;
            return;
        }
        const area = box.getBoundingClientRect();
        const bar = target.getBoundingClientRect();
        tip.textContent = target.dataset.tip;
        tip.style.left = `${bar.left - area.left + bar.width / 2}px`;
        tip.style.top = `${event.clientY - area.top - 8}px`;
        tip.hidden = false;
    });
    chart.addEventListener('mouseleave', () => (tip.hidden = true));
});

// Hero leaderboard: "Your product" gains votes and climbs from #4 to #1, then the loop restarts.
document.querySelectorAll('[data-rank-demo]').forEach((demo) => {
    const ROW = 3.5; // rem, matches the Blade component
    const rows = [...demo.querySelectorAll('[data-row]')];
    const you = demo.querySelector('[data-row="you"]');
    const badge = you.querySelector('[data-badge]');
    const start = rows.map((r) => Number(r.dataset.votes));
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const layout = () => {
        const order = [...rows].sort((a, b) => Number(b.dataset.votes) - Number(a.dataset.votes));
        order.forEach((row, i) => {
            row.style.transform = `translateY(${i * ROW}rem)`;
            row.querySelector('[data-rank]').textContent = i + 1;
        });
        return order.indexOf(you);
    };

    const setVotes = (row, n) => {
        row.dataset.votes = n;
        const count = row.querySelector('[data-count]');
        count.textContent = n;
        count.classList.remove('is-ticking');
        void count.offsetWidth;
        count.classList.add('is-ticking');
    };

    const setBadge = (text) => {
        badge.style.opacity = 0;
        setTimeout(() => { badge.textContent = text; badge.style.opacity = 1; }, 250);
    };

    if (reduce) {
        setVotes(you, 163);
        layout();
        badge.textContent = '#1 Product of the Day';
        return;
    }

    let timer;
    const step = () => {
        // Others drift up slowly; yours climbs faster.
        rows.forEach((r) => r !== you && Math.random() < 0.35 && setVotes(r, Number(r.dataset.votes) + 1));
        setVotes(you, Number(you.dataset.votes) + 6 + Math.floor(Math.random() * 6));
        const rank = layout();

        if (rank === 0) {
            setBadge('#1 Product of the Day');
            timer = setTimeout(reset, 3800);
        } else {
            if (rank < 3) setBadge(`Climbing · #${rank + 1}`);
            timer = setTimeout(step, 1400);
        }
    };

    const reset = () => {
        rows.forEach((r, i) => setVotes(r, start[i]));
        layout();
        setBadge('Launching today');
        timer = setTimeout(step, 1800);
    };

    // Only animate while visible.
    new IntersectionObserver(([entry]) => {
        clearTimeout(timer);
        if (entry.isIntersecting) timer = setTimeout(step, 1200);
    }).observe(demo);
});


// Homepage banner with real data: poll today's top 4, animate rank changes,
// pop "+1" on new votes, and cycle the latest-upvote ticker.
document.querySelectorAll('[data-rank-live]').forEach((board) => {
    const ROW = Number(board.dataset.rowRem || 3.5);
    const src = board.dataset.src;
    const rowsEl = board.querySelector('[data-rows]');
    const ticker = board.querySelector('[data-ticker]');
    let latest = JSON.parse(ticker?.dataset.latest || '[]');
    let tickIndex = 0;
    let pollTimer;
    let tickerTimer;

    const rows = () => [...rowsEl.querySelectorAll('[data-row]')];

    const bump = (el, value) => {
        const count = el.querySelector('[data-count]');
        const before = Number(count.textContent);
        if (before === value) return;
        count.textContent = value;
        count.classList.remove('is-ticking');
        void count.offsetWidth;
        count.classList.add('is-ticking');
        if (value > before) {
            const plus = document.createElement('span');
            plus.className = 'vote-plus';
            plus.textContent = `+${value - before}`;
            count.parentElement.appendChild(plus);
            setTimeout(() => plus.remove(), 1400);
        }
    };

    const shares = () => {
        const max = Math.max(1, ...rows().map((el) => Number(el.dataset.votes)));
        rows().forEach((el) => {
            const bar = el.querySelector('[data-share]');
            if (bar) bar.style.width = `${Math.round((Number(el.dataset.votes) / max) * 100)}%`;
        });
    };

    const apply = (top) => {
        const current = rows();
        const known = new Map(current.map((el) => [Number(el.dataset.row), el]));
        const incoming = new Set(top.map((p) => p.id));
        const spare = current.filter((el) => !incoming.has(Number(el.dataset.row)));

        top.forEach((p, i) => {
            let el = known.get(p.id);
            if (!el) {
                el = spare.shift();
                if (!el) return;
                el.dataset.row = p.id;
                el.href = p.url;
                el.querySelector('[data-name]').textContent = p.name;
                el.querySelector('[data-tagline]').textContent = p.tagline;
                el.querySelector('[data-logo]').src = p.logo;
                el.querySelector('[data-category]').textContent = p.category || '';
                el.querySelector('[data-sep]').classList.toggle('hidden', !p.category);
                el.querySelector('[data-count]').textContent = p.votes;
            }
            bump(el, p.votes);
            el.style.transform = `translateY(${i * ROW}rem)`;
            const rank = el.querySelector('[data-rank]');
            rank.textContent = i + 1;
            rank.classList.toggle('text-ink', i < 3);
            rank.classList.toggle('text-ink-2', i >= 3);
        });
        shares();
    };

    // Today / This week tabs
    board.querySelectorAll('[data-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            board.querySelectorAll('[data-tab]').forEach((t) => t.setAttribute('aria-selected', t === tab ? 'true' : 'false'));
            board.querySelectorAll('[data-panel]').forEach((panel) => (panel.hidden = panel.dataset.panel !== tab.dataset.tab));
        });
    });

    const showTicker = () => {
        if (!ticker || !latest.length) return;
        ticker.classList.remove('invisible');
        const item = ticker.querySelector('[data-ticker-item]');
        const ago = ticker.querySelector('[data-ticker-ago]');
        const next = latest[tickIndex % latest.length];
        item.style.opacity = 0;
        item.style.transform = 'translateY(6px)';
        ago.style.opacity = 0;
        setTimeout(() => {
            item.textContent = next.name;
            item.href = next.url;
            ago.textContent = `· ${next.ago}`;
            item.style.opacity = 1;
            item.style.transform = 'none';
            ago.style.opacity = 1;
        }, 250);
        tickIndex++;
    };

    const poll = async () => {
        if (document.hidden) return;
        try {
            const response = await fetch(src, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            apply(data.top || []);
            if (Array.isArray(data.latest) && data.latest.length) {
                const changed = JSON.stringify(data.latest[0]) !== JSON.stringify(latest[0]);
                latest = data.latest;
                if (changed) { tickIndex = 0; showTicker(); }
            }
        } catch {
            /* offline: keep what's shown */
        }
    };

    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Only poll and animate while the banner is on screen.
    new IntersectionObserver(([entry]) => {
        clearInterval(pollTimer);
        clearInterval(tickerTimer);
        if (!entry.isIntersecting) return;
        poll();
        pollTimer = setInterval(poll, 20000);
        if (!reduce && latest.length > 1) tickerTimer = setInterval(showTicker, 4500);
    }).observe(board);

    // Your own vote shows up right away.
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-vote]')) setTimeout(poll, 600);
    });
});



// Global chat: anonymous, polls every 4s while open. Text only (no HTML).
document.querySelectorAll('[data-chat]').forEach((root) => {
    const $ = (sel) => root.querySelector(sel);
    const panel = $('[data-chat-panel]');
    const log = $('[data-chat-log]');
    const empty = $('[data-chat-empty]');
    const form = $('[data-chat-form]');
    const input = form.querySelector('input');
    const error = $('[data-chat-error]');
    const unread = $('[data-chat-unread]');
    const renameForm = $('[data-chat-rename]');
    const token = () => document.querySelector('meta[name="csrf-token"]')?.content;

    let lastId = 0;
    let open = false;
    let loaded = false;
    let pollTimer;
    let unreadCount = 0;

    const timeOf = (iso) => new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const showError = (text) => {
        error.textContent = text;
        error.hidden = !text;
        if (text) setTimeout(() => (error.hidden = true), 5000);
    };

    const setOnline = (n) => root.querySelectorAll('[data-chat-online], [data-chat-online-count]').forEach((el) => (el.textContent = n));

    const render = (messages) => {
        if (!messages.length) return;
        const nearBottom = log.scrollHeight - log.scrollTop - log.clientHeight < 80;
        empty?.remove();

        for (const m of messages) {
            if (m.id <= lastId) continue;
            lastId = m.id;

            const row = document.createElement('div');
            row.className = m.mine ? 'flex flex-col items-end' : 'flex flex-col items-start';

            const meta = document.createElement('p');
            meta.className = 'mb-1 text-[0.6875rem] text-ink-2';
            const name = document.createElement('span');
            name.className = 'font-semibold text-ink';
            name.textContent = m.mine ? 'You' : m.name;
            meta.append(name, document.createTextNode(` · ${timeOf(m.time)}`));

            const bubble = document.createElement('p');
            bubble.className = m.mine
                ? 'max-w-[85%] rounded-2xl rounded-br-md bg-ink px-3.5 py-2 text-sm text-on-ink break-words'
                : 'max-w-[85%] rounded-2xl rounded-bl-md bg-fill px-3.5 py-2 text-sm break-words';
            bubble.textContent = m.body;

            row.append(meta, bubble);
            log.append(row);

            if (!open && loaded && !m.mine) unreadCount++;
        }

        if (open && (nearBottom || !loaded)) log.scrollTop = log.scrollHeight;
        unread.hidden = open || unreadCount === 0;
        unread.textContent = unreadCount > 9 ? '9+' : unreadCount;
    };

    const fetchMessages = async () => {
        try {
            const response = await fetch(`${root.dataset.src}${lastId ? `?after=${lastId}` : ''}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            $('[data-chat-me]').textContent = data.me;
            setOnline(data.online);
            render(data.messages || []);
            loaded = true;
        } catch {
            /* offline: try again next tick */
        }
    };

    const schedule = () => {
        clearInterval(pollTimer);
        if (document.hidden) return;
        // Fast while open; slow in the background (only after the chat was opened once).
        if (open) pollTimer = setInterval(fetchMessages, 4000);
        else if (loaded) pollTimer = setInterval(fetchMessages, 30000);
    };

    const setOpen = (value) => {
        open = value;
        panel.hidden = !open;
        $('[data-chat-open]').hidden = open && window.matchMedia('(max-width: 639px)').matches;
        if (open) {
            unreadCount = 0;
            unread.hidden = true;
            fetchMessages().then(() => (log.scrollTop = log.scrollHeight));
            setTimeout(() => input.focus(), 50);
        }
        schedule();
    };

    $('[data-chat-open]').addEventListener('click', () => setOpen(!open));
    $('[data-chat-close]').addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && open) setOpen(false); });
    document.addEventListener('visibilitychange', schedule);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = input.value.trim();
        if (!body) return;
        input.disabled = true;
        try {
            const response = await fetch(root.dataset.post, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token() },
                body: JSON.stringify({ body }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                showError(data.error || 'Message not sent. Try again.');
            } else {
                input.value = '';
                showError('');
                render([data.message]);
                log.scrollTop = log.scrollHeight;
            }
        } finally {
            input.disabled = false;
            input.focus();
        }
    });

    $('[data-chat-rename-open]').addEventListener('click', () => {
        renameForm.hidden = false;
        renameForm.querySelector('input').value = $('[data-chat-me]').textContent;
        renameForm.querySelector('input').focus();
    });
    $('[data-chat-rename-cancel]').addEventListener('click', () => (renameForm.hidden = true));
    renameForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const name = renameForm.querySelector('input').value.trim();
        const response = await fetch(root.dataset.rename, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token() },
            body: JSON.stringify({ name }),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) return showError(data.error || 'That name isn’t allowed.');
        $('[data-chat-me]').textContent = data.me;
        renameForm.hidden = true;
        showError('');
    });
});

// Times in the visitor's own zone: <time data-local-time="time|day|daytime" datetime="ISO">
document.querySelectorAll('[data-local-time]').forEach((el) => {
    const date = new Date(el.getAttribute('datetime'));
    if (Number.isNaN(date.getTime())) return;
    const formats = {
        time: { hour: 'numeric', minute: '2-digit' },
        day: { weekday: 'long', month: 'short', day: 'numeric' },
        daytime: { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' },
    };
    const text = date.toLocaleString([], formats[el.dataset.localTime] || formats.time);
    el.textContent = el.hasAttribute('data-local-suffix') && el.dataset.localTime !== 'day' ? `${text} your time` : text;
});

// Remember a signed-in maker's time zone so emails can use it (sent once, when it changes).
(() => {
    const body = document.body;
    if (!body.dataset.tzUrl) return;
    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    if (!tz || tz === body.dataset.userTz) return;
    fetch(body.dataset.tzUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
        body: JSON.stringify({ timezone: tz }),
    }).catch(() => {});
})();

// 3D flip clock: counts down to data-to; changed digits fold over like a split-flap display.
document.querySelectorAll('[data-flip-clock]').forEach((clock) => {
    const target = new Date(clock.dataset.to).getTime();
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const cards = {};
    clock.querySelectorAll('[data-digit]').forEach((el) => (cards[el.dataset.digit] = { el, value: null }));

    const set = (card, next) => {
        if (card.value === next) return;
        const [top, bottom, fold, unfold] = card.el.querySelectorAll('.flip-half > span');
        const prev = card.value ?? next;
        card.value = next;

        if (reduce || prev === next) {
            top.textContent = bottom.textContent = fold.textContent = unfold.textContent = next;
            return;
        }

        // Static top shows the new digit; the folding leaf still shows the old one.
        top.textContent = next;
        fold.textContent = prev;
        unfold.textContent = next;
        bottom.textContent = prev;

        card.el.classList.remove('is-flipping');
        void card.el.offsetWidth; // restart the animation
        card.el.classList.add('is-flipping');

        clearTimeout(card.timer);
        card.timer = setTimeout(() => {
            bottom.textContent = next;
            card.el.classList.remove('is-flipping');
        }, 620);
    };

    const tick = () => {
        let s = Math.max(0, Math.floor((target - Date.now()) / 1000));
        const parts = {
            hours: String(Math.floor(s / 3600) % 100).padStart(2, '0'),
            minutes: String(Math.floor((s % 3600) / 60)).padStart(2, '0'),
            seconds: String(s % 60).padStart(2, '0'),
        };
        for (const [unit, value] of Object.entries(parts)) {
            set(cards[`${unit}-0`], value[0]);
            set(cards[`${unit}-1`], value[1]);
        }
        if (s === 0 && !clock.dataset.reloading) {
            clock.dataset.reloading = '1';
            setTimeout(() => window.location.reload(), 3000); // new day: fresh board
        }
    };

    tick();
    setInterval(tick, 1000);
});

// Hero particle network: drifting dots that link when close, a few orange accents,
// real app logos as larger floating nodes (the spotlight one wears a ribbon), and dots
// that reach toward the cursor. Pauses off-screen and in background tabs; a still
// frame for reduced-motion users.
document.querySelectorAll('canvas[data-particles]').forEach((canvas) => {
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const LINK = 125;
    const LOGO_LINK = 170;
    const LOGO = 36;
    let w = 0;
    let h = 0;
    let dots = [];
    let logos = [];
    let ink = '23, 23, 23';
    let accent = '249, 115, 22';
    let surface = '#ffffff';
    let mouse = null;
    let running = false;
    let visible = true;
    let frame;

    const color = (cssVar) => {
        const probe = document.createElement('span');
        probe.style.color = `var(${cssVar})`;
        document.body.appendChild(probe);
        const rgb = getComputedStyle(probe).color.match(/\d+/g);
        probe.remove();
        return rgb ? rgb.slice(0, 3).join(', ') : null;
    };

    const readTheme = () => {
        ink = color('--ink') || ink;
        accent = color('--accent') || accent;
        const s = color('--surface');
        if (s) surface = `rgb(${s})`;
    };

    const nodes = JSON.parse(canvas.dataset.nodes || '[]').map((n) => {
        const img = new Image();
        img.decoding = 'async';
        img.src = n.logo;
        return { ...n, img };
    });

    const rand = (min, max) => min + Math.random() * (max - min);

    const resize = () => {
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        w = canvas.clientWidth;
        h = canvas.clientHeight;
        canvas.width = Math.round(w * ratio);
        canvas.height = Math.round(h * ratio);
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);

        const count = Math.max(22, Math.min(85, Math.round((w * h) / 12500)));
        dots = Array.from({ length: count }, (_, i) => ({
            x: rand(0, w), y: rand(0, h),
            vx: rand(-0.25, 0.25), vy: rand(-0.25, 0.25),
            r: rand(0.9, 2),
            accent: i % 9 === 0, // roughly 1 in 9 dots is orange
        }));

        // Logo nodes spread across the field, drifting slower than the dots.
        logos = nodes.map((n, i) => ({
            ...n,
            x: (w / (nodes.length + 1)) * (i + 1) + rand(-40, 40),
            y: rand(h * 0.2, h * 0.85),
            vx: rand(-0.12, 0.12) || 0.08,
            vy: rand(-0.1, 0.1) || 0.06,
        }));
    };

    const roundRect = (x, y, width, height, r) => {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + width, y, x + width, y + height, r);
        ctx.arcTo(x + width, y + height, x, y + height, r);
        ctx.arcTo(x, y + height, x, y, r);
        ctx.arcTo(x, y, x + width, y, r);
        ctx.closePath();
    };

    const line = (a, b, max, alpha, rgb = ink) => {
        const d = Math.hypot(a.x - b.x, a.y - b.y);
        if (d >= max) return;
        ctx.strokeStyle = `rgba(${rgb}, ${alpha * (1 - d / max)})`;
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(a.x, a.y);
        ctx.lineTo(b.x, b.y);
        ctx.stroke();
    };

    const draw = () => {
        ctx.clearRect(0, 0, w, h);

        for (let i = 0; i < dots.length; i++) {
            for (let j = i + 1; j < dots.length; j++) line(dots[i], dots[j], LINK, 0.12);
            if (mouse) line(dots[i], mouse, 160, 0.28, accent);
        }

        // Apps are the hubs: their links are a bit stronger.
        for (const l of logos) for (const d of dots) line(l, d, LOGO_LINK, 0.16);

        for (const d of dots) {
            ctx.fillStyle = d.accent ? `rgba(${accent}, 0.85)` : `rgba(${ink}, 0.32)`;
            ctx.beginPath();
            ctx.arc(d.x, d.y, d.accent ? d.r + 0.6 : d.r, 0, Math.PI * 2);
            ctx.fill();
        }

        for (const l of logos) {
            const x = l.x - LOGO / 2;
            const y = l.y - LOGO / 2;

            ctx.save();
            ctx.shadowColor = 'rgba(0, 0, 0, 0.18)';
            ctx.shadowBlur = 14;
            ctx.shadowOffsetY = 4;
            roundRect(x - 3, y - 3, LOGO + 6, LOGO + 6, 11);
            ctx.fillStyle = surface;
            ctx.fill();
            ctx.restore();
            roundRect(x - 3, y - 3, LOGO + 6, LOGO + 6, 11);
            ctx.strokeStyle = l.accent ? `rgba(${accent}, 0.9)` : `rgba(${ink}, 0.12)`;
            ctx.lineWidth = l.accent ? 1.5 : 1;
            ctx.stroke();

            if (l.img.complete && l.img.naturalWidth) {
                ctx.save();
                roundRect(x, y, LOGO, LOGO, 8);
                ctx.clip();
                ctx.drawImage(l.img, x, y, LOGO, LOGO);
                ctx.restore();
            }

            if (l.label) {
                ctx.font = '700 10px ui-sans-serif, system-ui, sans-serif';
                const tw = ctx.measureText(l.label).width + 16;
                const ty = y - 30;
                roundRect(l.x - tw / 2, ty, tw, 19, 9.5);
                ctx.fillStyle = l.accent ? `rgb(${accent})` : `rgb(${ink})`;
                ctx.fill();
                ctx.fillStyle = '#fff';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(l.label, l.x, ty + 10);
            }
        }
    };

    const step = () => {
        for (const d of dots) {
            d.x += d.vx;
            d.y += d.vy;
            if (d.x < -10) d.x = w + 10;
            if (d.x > w + 10) d.x = -10;
            if (d.y < -10) d.y = h + 10;
            if (d.y > h + 10) d.y = -10;
        }
        // Logos bounce softly inside the field so they stay visible.
        for (const l of logos) {
            l.x += l.vx;
            l.y += l.vy;
            if (l.x < 40 || l.x > w - 40) l.vx *= -1;
            if (l.y < 60 || l.y > h - 40) l.vy *= -1;
        }
        draw();
        frame = requestAnimationFrame(step);
    };

    const start = () => {
        if (running || reduce || !visible || document.hidden) return;
        running = true;
        frame = requestAnimationFrame(step);
    };
    const stop = () => {
        running = false;
        cancelAnimationFrame(frame);
    };

    readTheme();
    resize();
    draw();
    nodes.forEach((n) => n.img.addEventListener('load', () => { if (!running) draw(); }, { once: true }));

    new IntersectionObserver(([entry]) => {
        visible = entry.isIntersecting;
        if (visible) start(); else stop();
    }).observe(canvas);
    document.addEventListener('visibilitychange', () => { if (document.hidden) stop(); else start(); });
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => { readTheme(); draw(); });

    // The canvas sits behind the content, so track the pointer at window level.
    window.addEventListener('pointermove', (event) => {
        const rect = canvas.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        mouse = x >= 0 && y >= 0 && x <= rect.width && y <= rect.height ? { x, y } : null;
    }, { passive: true });

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => { resize(); draw(); }, 150);
    });
});

// Homepage board: category filter chips for today's launches.
document.querySelectorAll('[data-board]').forEach((board) => {
    const chips = board.querySelectorAll('[data-filter]');
    chips.forEach((chip) => chip.addEventListener('click', () => {
        const slug = chip.dataset.filter;
        chips.forEach((c) => c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'));
        let shown = 0;
        board.querySelectorAll('li[data-category]').forEach((row) => {
            const match = !slug || row.dataset.category === slug;
            row.hidden = !match;
            if (match) shown++;
        });
        const empty = board.querySelector('[data-board-empty]');
        if (empty) empty.hidden = shown > 0;
    }));
});

// Signed-in toggles (Save, Notify me, comment upvotes): POST, then flip aria-pressed and any count.
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-toggle-url]');
    if (!button) return;
    event.preventDefault();

    if (button.dataset.login) {
        window.location.href = button.dataset.login;
        return;
    }
    if (button.dataset.busy) return;
    button.dataset.busy = '1';

    try {
        const response = await fetch(button.dataset.toggleUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
        });
        if (response.status === 401) {
            window.location.href = '/login';
            return;
        }
        if (!response.ok) return;

        const { active, count } = await response.json();
        document.querySelectorAll(`[data-toggle-url="${CSS.escape(button.dataset.toggleUrl)}"]`).forEach((el) => {
            el.setAttribute('aria-pressed', active ? 'true' : 'false');
            const counter = el.querySelector('[data-count]');
            if (counter && count !== undefined) counter.textContent = count;
        });
    } finally {
        delete button.dataset.busy;
    }
});

// Popovers (notifications): toggle on click, close on outside click or Escape.
// Opening the bell marks notifications read once.
document.querySelectorAll('[data-popover]').forEach((root) => {
    const toggle = root.querySelector('[data-popover-toggle]');
    const panel = root.querySelector('[data-popover-panel]');
    const setOpen = (open) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open && toggle.dataset.readUrl) {
            fetch(toggle.dataset.readUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' } });
            toggle.dataset.readUrl = '';
            root.querySelector('[data-unread]')?.remove();
        }
    };
    toggle.addEventListener('click', () => setOpen(panel.hidden));
    document.addEventListener('click', (event) => { if (!root.contains(event.target)) setOpen(false); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') setOpen(false); });
});

// Homepage feed: "Show all" inside a day, and "Show earlier launches" loads older days.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-show-all]');
    if (!button) return;
    button.closest('[data-feed-day]').querySelectorAll('[data-more]').forEach((row) => (row.hidden = false));
    button.remove();
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-feed-next]');
    if (!button || button.dataset.busy) return;
    button.dataset.busy = '1';
    const label = button.textContent;
    button.textContent = 'Loading…';

    try {
        const response = await fetch(button.dataset.feedNext, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Feed request failed');
        const { html, next } = await response.json();
        button.closest('[data-feed-more]').insertAdjacentHTML('beforebegin', html);
        if (next) {
            button.dataset.feedNext = button.dataset.feedNext.replace(/\d{4}-\d{2}-\d{2}$/, next);
            button.textContent = label;
        } else {
            button.remove();
        }
    } catch {
        button.textContent = label;
    } finally {
        delete button.dataset.busy;
    }
});

// Search palette: Ctrl/Cmd+K or "/" anywhere. Debounced results for apps, categories and makers.
document.querySelectorAll('[data-search-palette]').forEach((palette) => {
    const input = palette.querySelector('[data-search-input]');
    const results = palette.querySelector('[data-search-results]');
    const hint = results.innerHTML;
    let active = -1;
    let timer;
    let lastQuery = '';
    let controller;

    const entities = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => entities[c]);
    const items = () => [...results.querySelectorAll('[role="option"]')];

    const open = () => {
        palette.hidden = false;
        document.documentElement.style.overflow = 'hidden';
        input.focus();
        input.select();
    };
    const close = () => {
        palette.hidden = true;
        document.documentElement.style.overflow = '';
    };

    const highlight = (index) => {
        const list = items();
        active = list.length ? (index + list.length) % list.length : -1;
        list.forEach((el, i) => el.setAttribute('aria-selected', i === active ? 'true' : 'false'));
        list[active]?.scrollIntoView({ block: 'nearest' });
    };

    const gridIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="6.5" height="6.5" rx="1.75"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.75"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.75"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.75"/></svg>';

    const row = (item, kind) => {
        let media;
        if (kind === 'categories') {
            media = `<span class="grid size-9 shrink-0 place-items-center rounded-[0.6rem] bg-fill text-ink-2">${gridIcon}</span>`;
        } else if (item.image) {
            media = `<img src="${escapeHtml(item.image)}" alt="" class="size-9 shrink-0 ${kind === 'makers' ? 'rounded-full' : 'rounded-[0.6rem]'} object-cover outline outline-1 -outline-offset-1 outline-line">`;
        } else {
            media = `<span class="grid size-9 shrink-0 place-items-center rounded-full bg-fill text-xs font-semibold">${escapeHtml(item.initials)}</span>`;
        }
        const meta = item.votes !== undefined ? `<span class="shrink-0 text-xs font-medium tabular-nums text-ink-2">${item.votes} upvotes</span>` : '';
        const sub = item.sub ? `<span class="block truncate text-xs text-ink-2">${escapeHtml(item.sub)}</span>` : '';
        return `<a role="option" aria-selected="false" href="${escapeHtml(item.url)}" class="search-option flex items-center gap-3 px-4 py-2">${media}<span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium">${escapeHtml(item.name)}</span>${sub}</span>${meta}</a>`;
    };

    const render = (data, query) => {
        const groups = [['apps', 'Apps'], ['categories', 'Categories'], ['makers', 'Makers']]
            .filter(([key]) => data[key]?.length)
            .map(([key, title]) => `<p class="px-4 pt-2 pb-1 text-[0.6875rem] font-semibold uppercase tracking-wide text-ink-3">${title}</p>${data[key].map((item) => row(item, key)).join('')}`);
        const everything = `<a role="option" aria-selected="false" href="${palette.dataset.discover}?q=${encodeURIComponent(query)}" class="search-option flex items-center gap-3 px-4 py-2.5 text-sm text-ink-2"><span class="grid size-9 shrink-0 place-items-center text-base">↵</span>Search everything for “${escapeHtml(query)}”</a>`;
        results.innerHTML = groups.length
            ? `${groups.join('')}<div class="mt-1 border-t border-line pt-1">${everything}</div>`
            : `<p class="px-4 pt-5 pb-2 text-center text-sm text-ink-2">No matches for “${escapeHtml(query)}”.</p>${everything}`;
        input.setAttribute('aria-expanded', 'true');
        highlight(0);
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        const query = input.value.trim();
        if (query.length < 2) {
            results.innerHTML = hint;
            active = -1;
            lastQuery = '';
            input.setAttribute('aria-expanded', 'false');
            return;
        }
        timer = setTimeout(async () => {
            if (query === lastQuery) return;
            lastQuery = query;
            controller?.abort();
            controller = new AbortController();
            try {
                const response = await fetch(`${palette.dataset.url}?q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' }, signal: controller.signal });
                if (response.ok && input.value.trim() === query) render(await response.json(), query);
            } catch {
                // aborted or offline
            }
        }, 160);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') { event.preventDefault(); highlight(active + 1); }
        if (event.key === 'ArrowUp') { event.preventDefault(); highlight(active - 1); }
    });

    palette.querySelector('[data-search-form]').addEventListener('submit', (event) => {
        event.preventDefault();
        const chosen = items()[active];
        const query = input.value.trim();
        if (chosen) window.location.href = chosen.href;
        else if (query) window.location.href = `${palette.dataset.discover}?q=${encodeURIComponent(query)}`;
    });

    results.addEventListener('mousemove', (event) => {
        const option = event.target.closest('[role="option"]');
        if (option && option.getAttribute('aria-selected') !== 'true') highlight(items().indexOf(option));
    });

    palette.querySelectorAll('[data-search-close]').forEach((el) => el.addEventListener('click', close));

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-search-open]');
        if (!trigger) return;
        event.preventDefault();
        open();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !palette.hidden) {
            close();
            return;
        }
        const typing = event.target.closest('input, textarea, select, [contenteditable]');
        const isShortcut = (event.key === 'k' || event.key === 'K') && (event.metaKey || event.ctrlKey);
        if (isShortcut || (event.key === '/' && !typing && !event.metaKey && !event.ctrlKey && !event.altKey)) {
            event.preventDefault();
            if (palette.hidden) open();
            else close();
        }
    });
});

// Product gallery: thumbnails swap the main image; clicking the main image opens a lightbox.
document.querySelectorAll('[data-gallery]').forEach((gallery) => {
    const main = gallery.querySelector('[data-gallery-main]');
    const thumbs = [...gallery.querySelectorAll('[data-gallery-thumb]')];
    const lightbox = gallery.querySelector('[data-lightbox]');
    const lightboxImage = lightbox?.querySelector('img');
    let index = 0;

    const show = (i) => {
        if (!thumbs.length) return;
        index = (i + thumbs.length) % thumbs.length;
        main.src = thumbs[index].dataset.galleryThumb;
        if (lightboxImage) lightboxImage.src = main.src;
        thumbs.forEach((t, n) => t.setAttribute('aria-current', n === index ? 'true' : 'false'));
    };
    const setLightbox = (open) => {
        if (!lightbox) return;
        lightbox.hidden = !open;
        document.documentElement.style.overflow = open ? 'hidden' : '';
        if (open) lightboxImage.src = main.src;
    };

    thumbs.forEach((thumb, i) => thumb.addEventListener('click', () => show(i)));
    main.closest('button')?.addEventListener('click', () => setLightbox(true));
    lightbox?.querySelector('[data-lightbox-close]')?.addEventListener('click', () => setLightbox(false));
    lightbox?.querySelector('[data-lightbox-prev]')?.addEventListener('click', (e) => { e.stopPropagation(); show(index - 1); });
    lightbox?.querySelector('[data-lightbox-next]')?.addEventListener('click', (e) => { e.stopPropagation(); show(index + 1); });
    lightbox?.addEventListener('click', (e) => { if (e.target === lightbox) setLightbox(false); });
    document.addEventListener('keydown', (event) => {
        if (!lightbox || lightbox.hidden) return;
        if (event.key === 'Escape') setLightbox(false);
        if (event.key === 'ArrowLeft') show(index - 1);
        if (event.key === 'ArrowRight') show(index + 1);
    });
});

// Animated numbers: [data-countup] counts up from 0 the first time it scrolls into view.
// tweenNumber() is also used when live presence numbers change.
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const formatNumber = (n) => Math.round(n).toLocaleString('en-US');

function tweenNumber(el, to, duration = 1200) {
    const from = Number(el.dataset.current ?? 0);
    el.dataset.current = to;
    if (reducedMotion || from === to) {
        el.textContent = formatNumber(to);
        return;
    }
    const start = performance.now();
    const step = (now) => {
        const t = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - t, 3);
        el.textContent = formatNumber(from + (to - from) * eased);
        if (t < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
}

const counters = document.querySelectorAll('[data-countup]');
if ('IntersectionObserver' in window && !reducedMotion) {
    const counterObserver = new IntersectionObserver((entries) => entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        counterObserver.unobserve(entry.target);
        entry.target.dataset.current = 0;
        tweenNumber(entry.target, Number(entry.target.dataset.countup), 1400);
    }), { threshold: 0.4 });
    counters.forEach((el) => {
        el.dataset.current = el.dataset.countup;
        counterObserver.observe(el);
    });
} else {
    counters.forEach((el) => (el.dataset.current = el.dataset.countup));
}

// Presence: check in now and every 30s while the tab is visible; update every live number on the page.
(() => {
    let timer;
    const ping = async () => {
        if (document.visibilityState !== 'visible') return;
        try {
            const response = await fetch('/presence', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const stats = await response.json();
            document.querySelectorAll('[data-presence]').forEach((el) => {
                const value = stats[el.dataset.presence];
                if (value === undefined) return;
                el.dataset.countup = value;
                tweenNumber(el, value, 900);
            });
            document.querySelectorAll('[data-presence-word]').forEach((el) => {
                el.textContent = stats.live === 1 ? 'person is' : 'people are';
            });
        } catch {
            // offline: keep the last numbers
        }
    };
    const schedule = () => {
        clearInterval(timer);
        timer = setInterval(ping, 30000);
    };
    ping();
    schedule();
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            ping();
            schedule();
        }
    });
})();

// Relative times ("3m ago") that stay fresh.
const timeAgo = (iso) => {
    const seconds = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000);
    if (seconds < 45) return 'now';
    const units = [['y', 31536000], ['mo', 2592000], ['w', 604800], ['d', 86400], ['h', 3600], ['m', 60]];
    for (const [unit, size] of units) {
        if (seconds >= size) return `${Math.floor(seconds / size)}${unit} ago`;
    }
    return '1m ago';
};
setInterval(() => document.querySelectorAll('time[data-ago]').forEach((el) => (el.textContent = timeAgo(el.dateTime))), 30000);

// "Happening now" stream: events enter at the top and push the oldest out at the bottom.
// It plays recent activity in the order it happened, picks up new events as they arrive,
// and starts the replay over once it has caught up and things are quiet.
document.querySelectorAll('[data-stream]').forEach((stream) => {
    const list = stream.querySelector('[data-stream-list]');
    const status = document.querySelector('[data-stream-status]');
    const icons = JSON.parse(stream.dataset.icons || '{}');
    const visible = 6;
    let events = [];
    let queue = [];
    let shown = new Set();
    let running = false;
    let timer;

    const escapeText = (value) => {
        const span = document.createElement('span');
        span.textContent = value ?? '';
        return span.innerHTML;
    };

    const render = (event) => {
        const li = document.createElement('li');
        li.className = 'stream-item is-entering';
        li.dataset.id = event.id;
        const name = event.url
            ? `<a href="${escapeText(event.url)}" class="font-semibold hover:underline">${escapeText(event.product)}</a>`
            : `<span class="font-semibold">${escapeText(event.product)}</span>`;
        li.innerHTML = `<span class="app-icon size-9 !rounded-[0.6rem]"><img src="${escapeText(event.logo)}" alt="" loading="lazy"></span>
            <span class="min-w-0 flex-1 truncate text-sm">${name} <span class="text-ink-2">${escapeText(event.text)}</span></span>
            <time class="shrink-0 text-xs tabular-nums text-ink-3" datetime="${escapeText(event.at)}" data-ago>${timeAgo(event.at)}</time>
            <span class="stream-type">${icons[event.type] ?? ''}</span>`;
        return li;
    };

    const push = (event) => {
        list.prepend(render(event));
        shown.add(event.id);
        const items = [...list.querySelectorAll('.stream-item:not(.is-leaving)')];
        items.slice(visible).forEach((old) => {
            old.classList.add('is-leaving');
            old.addEventListener('animationend', () => old.remove(), { once: true });
        });
    };

    const restartReplay = () => {
        // Oldest first, so the stream ends on the newest event.
        queue = [...events].reverse();
        shown = new Set();
        [...list.children].forEach((old, i) => {
            old.style.animationDelay = `${i * 60}ms`;
            old.classList.remove('is-entering');
            old.classList.add('is-leaving');
            old.addEventListener('animationend', () => old.remove(), { once: true });
        });
    };

    const tick = () => {
        if (document.visibilityState === 'visible') {
            if (!queue.length && events.length) restartReplay();
            const next = queue.shift();
            if (next) push(next);
        }
        timer = setTimeout(tick, queue.length ? 2600 : 3600);
    };

    const load = async () => {
        try {
            const response = await fetch(stream.dataset.url, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const fresh = (await response.json()).events ?? [];
            const known = new Set(events.map((e) => e.id));
            const arrivals = fresh.filter((e) => !known.has(e.id)).reverse();
            events = fresh;
            if (!running) {
                // First tick clears the static list and replays recent activity oldest to newest.
                queue = [];
                running = true;
                if (status) status.textContent = 'Live · updates as it happens';
                timer = setTimeout(tick, 1600);
            } else if (arrivals.length) {
                // Brand-new events jump the queue.
                queue = [...arrivals.filter((e) => !shown.has(e.id)), ...queue];
            }
        } catch {
            // try again on the next poll
        }
    };

    if (reducedMotion) return;

    const start = () => {
        load();
        setInterval(() => document.visibilityState === 'visible' && load(), 15000);
    };

    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
            if (entries.some((e) => e.isIntersecting)) {
                io.disconnect();
                start();
            }
        });
        io.observe(stream);
    } else {
        start();
    }
    window.addEventListener('pagehide', () => clearTimeout(timer));
});
