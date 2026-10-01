import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// x-reveal: fades/slides an element up once it scrolls into view. Respects
// prefers-reduced-motion via the CSS in app.css (the .reveal class becomes a
// no-op there), so this JS only ever toggles a class, never inline styles.
document.addEventListener('alpine:init', () => {
    Alpine.directive('reveal', (el, { modifiers }) => {
        el.classList.add('reveal');
        const delay = modifiers.find((m) => /^\d+$/.test(m));
        if (delay) el.style.animationDelay = `${delay}ms`;

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    el.classList.add('is-visible');
                    observer.disconnect();
                }
            },
            { threshold: 0.15 }
        );
        observer.observe(el);
    });
});

// Global toast notifications — used for cart add/update feedback. A single
// store (not per-component state) so any page/partial can call
// Alpine.store('toast').push(...) without wiring up its own queue.
document.addEventListener('alpine:init', () => {
    Alpine.store('toast', {
        items: [],
        push(message, tone = 'success') {
            const id = Date.now() + Math.random();
            this.items.push({ id, message, tone });
            setTimeout(() => {
                this.items = this.items.filter((item) => item.id !== id);
            }, 3200);
        },
    });
});

// Shared "What does Farmtech Verified mean?" modal — one instance in the
// layout, triggered from any product-card badge via this store, so the
// explanation doesn't need duplicating (or an invalid nested-link/button)
// inside every card.
document.addEventListener('alpine:init', () => {
    Alpine.store('verifiedModal', { open: false });
});

// Quick View — one shared modal in the layout, opened from any catalog card
// with the product's own already-rendered data (title/price/spec/image),
// never a second fetch for data the page already has.
document.addEventListener('alpine:init', () => {
    Alpine.store('quickView', {
        open: false,
        product: null,
        show(product) {
            this.product = product;
            this.open = true;
        },
        close() {
            this.open = false;
        },
    });
});

// Compare — up to 3 products, persisted in localStorage so the selection
// survives across pages (a buyer comparing scales might browse two separate
// category pages before opening the drawer). Cards push a real snapshot of
// their own already-rendered data on toggle; the drawer itself fetches full
// spec data fresh from the server when opened, since specs aren't carried
// in the lightweight snapshot.
document.addEventListener('alpine:init', () => {
    const STORAGE_KEY = 'farmtech_compare';

    Alpine.store('compare', {
        items: [],
        max: 3,
        open: false,
        data: null,
        loading: false,
        init() {
            try {
                const raw = localStorage.getItem(STORAGE_KEY);
                this.items = raw ? JSON.parse(raw) : [];
            } catch (e) {
                this.items = [];
            }
        },
        persist() {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(this.items));
        },
        has(id) {
            return this.items.some((item) => item.id === id);
        },
        toggle(product) {
            if (this.has(product.id)) {
                this.items = this.items.filter((item) => item.id !== product.id);
                this.persist();
                return;
            }
            if (this.items.length >= this.max) {
                Alpine.store('toast').push(`You can compare up to ${this.max} items at a time.`, 'error');
                return;
            }
            this.items = [...this.items, product];
            this.persist();
        },
        remove(id) {
            this.items = this.items.filter((item) => item.id !== id);
            this.data = this.data ? { ...this.data, products: this.data.products.filter((p) => p.id !== id) } : null;
            this.persist();
        },
        clear() {
            this.items = [];
            this.data = null;
            this.persist();
        },
        async openDrawer() {
            this.open = true;
            if (this.items.length === 0) return;
            this.loading = true;
            try {
                const ids = this.items.map((item) => item.id).join(',');
                const response = await fetch(`/admin/shop/compare?ids=${ids}`, { headers: { Accept: 'application/json' } });
                this.data = response.ok ? await response.json() : null;
            } catch (e) {
                this.data = null;
            } finally {
                this.loading = false;
            }
        },
        closeDrawer() {
            this.open = false;
        },
    });
});

// Cart drawer — server session remains the source of truth (Cart::summary());
// this store just mirrors it so add/update/remove can happen without a full
// page reload. Bootstrapped from a JSON blob the layout renders server-side
// (so the drawer already has the right count/items on first paint, no extra
// fetch), then kept in sync by replacing its state with whatever the server
// returns from each request — never computed optimistically client-side, so
// it can't drift from real stock/price rules enforced server-side.
document.addEventListener('alpine:init', () => {
    Alpine.store('cart', {
        open: false,
        loading: false,
        items: [],
        count: 0,
        subtotal: 0,
        init() {
            const boot = document.getElementById('cart-bootstrap');
            if (!boot) return;
            try {
                this.apply(JSON.parse(boot.textContent));
            } catch (e) {
                // malformed/missing bootstrap payload — drawer just opens empty
            }
        },
        apply(data) {
            this.items = data.items ?? [];
            this.count = data.count ?? 0;
            this.subtotal = data.subtotal ?? 0;
        },
        async request(url, body) {
            this.loading = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
                    body,
                });
                if (!response.ok) {
                    const err = await response.json().catch(() => ({}));
                    Alpine.store('toast').push(err.message || 'Something went wrong — please try again.', 'error');
                    return null;
                }
                const data = await response.json();
                this.apply(data);
                return data;
            } catch (e) {
                Alpine.store('toast').push('Network error — please try again.', 'error');
                return null;
            } finally {
                this.loading = false;
            }
        },
        async submitForm(form) {
            const data = await this.request(form.action, new FormData(form));
            if (data) {
                this.open = true;
                Alpine.store('toast').push('Added to your cart.', 'success');
            }
        },
        async updateQuantity(item, quantity) {
            if (quantity < 1) return this.removeItem(item);
            const body = new FormData();
            body.append('_method', 'PATCH');
            body.append('quantity', quantity);
            if (item.variant_id) body.append('variant_id', item.variant_id);
            await this.request(`/admin/shop/cart/${item.product_id}`, body);
        },
        async removeItem(item) {
            const body = new FormData();
            body.append('_method', 'DELETE');
            if (item.variant_id) body.append('variant_id', item.variant_id);
            await this.request(`/admin/shop/cart/${item.product_id}`, body);
        },
    });
});

// Cinematic entrance sequence, once per browser tab session — read before
// marking played, so THIS page load still gets `alreadyPlayed: false` and
// plays the animation; every subsequent navigation in the same tab sees it
// already set and skips straight to the settled state.
document.addEventListener('alpine:init', () => {
    const alreadyPlayed = sessionStorage.getItem('intro_animated') === '1';
    sessionStorage.setItem('intro_animated', '1');
    Alpine.store('intro', { alreadyPlayed });
});

Alpine.start();

// ── Herd Manager polish ─────────────────────────────────────────────────
document.addEventListener('alpine:init', () => {
    // First-run tour: highlights one element at a time ([data-tour="…"]).
    Alpine.data('tour', (steps, doneUrl, autoStart) => ({
        steps, i: 0, open: false, rect: null,
        init() {
            if (autoStart) setTimeout(() => this.start(), 700);
            window.addEventListener('start-tour', () => this.start());
            window.addEventListener('resize', () => this.place());
            window.addEventListener('scroll', () => this.place(), { passive: true });
        },
        get step() { return this.steps[this.i] || {}; },
        start() { this.i = 0; this.open = true; this.$nextTick(() => this.go()); },
        target() { return this.step.target ? document.querySelector(`[data-tour="${this.step.target}"]`) : null; },
        go() {
            const el = this.target();
            if (el) { el.scrollIntoView({ block: 'center', behavior: 'smooth' }); setTimeout(() => this.place(), 380); } else { this.rect = null; }
        },
        place() {
            if (!this.open) return;
            const el = this.target();
            if (!el) { this.rect = null; return; }
            const r = el.getBoundingClientRect();
            this.rect = { top: r.top - 8, left: r.left - 8, width: r.width + 16, height: r.height + 16 };
        },
        next() { if (this.i < this.steps.length - 1) { this.i++; this.go(); } else { this.finish(); } },
        back() { if (this.i > 0) { this.i--; this.go(); } },
        finish() {
            this.open = false;
            fetch(doneUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content, Accept: 'application/json' } });
        },
        get spot() { return this.rect ? `top:${this.rect.top}px;left:${this.rect.left}px;width:${this.rect.width}px;height:${this.rect.height}px` : ''; },
        get card() {
            if (!this.rect || window.innerWidth < 640) return '';
            const below = this.rect.top + this.rect.height + 14;
            const top = below + 230 < window.innerHeight ? below : Math.max(16, this.rect.top - 244);
            const left = Math.min(Math.max(16, this.rect.left), window.innerWidth - 396);
            return `top:${top}px;left:${left}px`;
        },
    }));

    // Numbers that count up when they appear: <span x-countup>1 234</span>
    Alpine.directive('countup', (el) => {
        const raw = el.textContent.trim();
        const target = parseFloat(raw.replace(/[^\d.-]/g, ''));
        if (isNaN(target) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const decimals = (raw.split('.')[1] || '').replace(/\D/g, '').length;
        const prefix = raw.startsWith('+') ? '+' : '';
        const t0 = performance.now(), dur = 900;
        const tick = (now) => {
            const p = Math.min(1, (now - t0) / dur), e = 1 - Math.pow(1 - p, 3);
            const v = (target * e).toFixed(decimals);
            el.textContent = prefix + Number(v).toLocaleString('en-ZA', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).replace(/,/g, ' ');
            if (p < 1) requestAnimationFrame(tick);
        };
        el.textContent = prefix + '0';
        requestAnimationFrame(tick);
    });
});

// A small, satisfying burst for big moments (imports done, first pairing…).
window.farmtechConfetti = () => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const colours = ['#B8732E', '#E3B27A', '#15140F', '#3F7A3A', '#F4F1EA'];
    for (let i = 0; i < 70; i++) {
        const d = document.createElement('i');
        const size = 6 + Math.random() * 6;
        Object.assign(d.style, {
            position: 'fixed', zIndex: 80, left: '50%', top: '38%', width: `${size}px`, height: `${size * 0.45}px`,
            background: colours[i % colours.length], borderRadius: '2px', pointerEvents: 'none',
        });
        document.body.appendChild(d);
        const a = Math.random() * Math.PI * 2, v = 180 + Math.random() * 260;
        d.animate([
            { transform: 'translate(-50%,-50%) rotate(0)', opacity: 1 },
            { transform: `translate(${Math.cos(a) * v}px, ${Math.sin(a) * v + 260}px) rotate(${Math.random() * 720}deg)`, opacity: 0 },
        ], { duration: 1300 + Math.random() * 700, easing: 'cubic-bezier(.2,.7,.3,1)' }).onfinish = () => d.remove();
    }
};
