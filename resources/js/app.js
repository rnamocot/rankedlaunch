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

// Launch plan picker: show the date grid only for paid plans
document.querySelectorAll('[data-plan-form]').forEach((form) => {
    const sync = () => {
        const plan = form.querySelector('input[name="plan"]:checked')?.value;
        const dates = form.querySelector('[data-paid-dates]');
        const submit = form.querySelector('[data-plan-submit]');
        if (dates) dates.hidden = !plan || plan === 'free';

        // Pro has its own daily cap: grey out dates with no Pro spots left.
        form.querySelectorAll('input[name="launch_date"]').forEach((input) => {
            const left = Number(input.dataset.left);
            const proLeft = Number(input.dataset.proLeft);
            const available = plan === 'featured' ? proLeft > 0 : left > 0;
            const label = input.closest('[data-date]');
            const note = label.querySelector('[data-date-note]');
            input.disabled = !available;
            if (!available && input.checked) input.checked = false;
            label.classList.toggle('opacity-35', !available);
            label.classList.toggle('cursor-not-allowed', !available);
            label.classList.toggle('cursor-pointer', available);
            if (left === 0) note.textContent = 'Full';
            else if (plan === 'featured') note.textContent = proLeft === 0 ? 'Pro full' : `${proLeft} Pro left`;
            else note.textContent = left <= 3 ? `${left} left` : '';
        });
        if (submit && plan) submit.textContent = submit.dataset[plan] ?? submit.textContent;
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

// Press "/" anywhere to search (skipped while typing in a field).
document.addEventListener('keydown', (event) => {
    if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) return;
    if (event.target.closest('input, textarea, select, [contenteditable]')) return;
    event.preventDefault();
    const field = document.querySelector('#q, #home-search');
    if (field) field.focus();
    else window.location.href = '/discover';
});
