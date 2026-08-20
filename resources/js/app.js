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
