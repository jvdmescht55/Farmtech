/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                // rev. 11 — LOCKED design-system palette. Every green on the
                // site must come from this list (brand.* / mint) — no other
                // green hex should appear anywhere in the codebase. Only
                // these exact values are the source of truth; midnight and
                // precision below are the two non-green tokens kept for
                // roles the locked palette doesn't name (a dark admin-nav
                // surface, technical-spec amber highlighting).
                midnight: {
                    DEFAULT: '#0F172A',
                    dark: '#0B132B',
                    light: '#1E293B',
                },
                brand: {
                    900: '#0B4A36', // Primary
                    950: '#063525', // Dark
                    975: '#04291D', // Darkest
                },
                mint: {
                    DEFAULT: '#14A875', // Accent
                    dark: '#0F8C61',
                    light: '#5FCBA0',
                },
                alert: {
                    DEFAULT: '#D97706', // Warning
                    dark: '#92400E',
                    light: '#FBBF24',
                },
                success: '#149B70',
                error: '#C63C3C',
                // Technical-spec highlight color (frequencies, tolerances, battery
                // ratings) — visually distinct from `alert`'s stock-warning role
                // even though both land in the amber family. Not part of the
                // locked 7-role palette; kept for this one specific purpose.
                precision: {
                    DEFAULT: '#D97706',
                    dark: '#92400E',
                    light: '#FBBF24',
                },
                // Aliases so admin views (kept visually simpler/utilitarian on
                // purpose) still resolve to the same real palette above.
                farmtech: {
                    green: '#0B4A36',
                    'green-dark': '#063525',
                    gold: '#D97706',
                    cream: '#F6F8F6',
                },
                canvas: '#F6F8F6', // Page background
                border: '#DCE5E0', // Border
                charcoal: '#10231C', // Main text
                ink: {
                    secondary: '#5E6F67', // Secondary text
                    muted: '#82918B', // Muted text
                },
            },
            fontFamily: {
                // rev. 12 — premium-catalogue typography: Fraunces (a soft-serif with
                // an optical-size axis, so it reads well both huge on a hero and small
                // on a card title) for `font-display` — hero/section headings, product
                // titles, PDP names. Inter stays the workhorse for body copy, nav, forms,
                // and anywhere density matters. `font-mono` (IBM Plex Mono) is unchanged:
                // reserved for model numbers, SKUs, dimensions, technical values.
                display: ['"Fraunces"', 'ui-serif', 'Georgia', 'serif'],
                body: ['"Inter"', 'sans-serif'],
                mono: ['"IBM Plex Mono"', 'monospace'],
            },
            // rev. 11 — locked spacing scale: 4/8/12/16/24/32/48/64/80/96/120px.
            // Tailwind's own defaults already cover every step except 120px
            // (p-1..p-24 = 4..96px); this adds the one missing step rather
            // than redefining the whole scale.
            spacing: {
                30: '7.5rem', // 120px
            },
            backgroundImage: {
                'grain': "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.035'/%3E%3C/svg%3E\")",
            },
            keyframes: {
                'reveal-up': {
                    '0%': { opacity: '0', transform: 'translateY(14px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'stamp-in': {
                    '0%': { opacity: '0', transform: 'rotate(-16deg) scale(0.6)' },
                    '60%': { opacity: '1', transform: 'rotate(-8deg) scale(1.08)' },
                    '100%': { opacity: '1', transform: 'rotate(-8deg) scale(1)' },
                },
                'ticker': {
                    '0%': { transform: 'translateX(0)' },
                    '100%': { transform: 'translateX(-50%)' },
                },
                'pop-in': {
                    '0%': { transform: 'scale(0.5)' },
                    '50%': { transform: 'scale(1.25)' },
                    '100%': { transform: 'scale(1)' },
                },
                'toast-in': {
                    '0%': { opacity: '0', transform: 'translateY(-8px) translateX(-50%)' },
                    '100%': { opacity: '1', transform: 'translateY(0) translateX(-50%)' },
                },
                'glow-pulse': {
                    '0%, 100%': { boxShadow: '0 0 0 0 rgba(16,185,129,0.45)' },
                    '50%': { boxShadow: '0 0 0 10px rgba(16,185,129,0)' },
                },
                // Cinematic first-load entrance sequence (see x-intro-* usages) — plays once per
                // browser session (sessionStorage flag), never on subsequent in-session navigation.
                'slide-down-in': {
                    '0%': { opacity: '0', transform: 'translateY(-100%)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'scale-in': {
                    '0%': { opacity: '0', transform: 'scale(0.95)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                // Slow, near-imperceptible drift on hero/feature photography —
                // the closest a static supplier photo gets to "cinematic
                // imagery" without a real video/360 asset to work with.
                'ken-burns': {
                    '0%': { transform: 'scale(1)' },
                    '100%': { transform: 'scale(1.08)' },
                },
            },
            animation: {
                'reveal-up': 'reveal-up 0.6s cubic-bezier(0.16,1,0.3,1) both',
                'stamp-in': 'stamp-in 0.5s cubic-bezier(0.34,1.56,0.64,1) both',
                'ticker': 'ticker 22s linear infinite',
                'pop-in': 'pop-in 0.4s cubic-bezier(0.34,1.56,0.64,1) both',
                'toast-in': 'toast-in 0.3s cubic-bezier(0.16,1,0.3,1) both',
                'glow-pulse': 'glow-pulse 2.2s ease-out infinite',
                'slide-down-in': 'slide-down-in 0.5s cubic-bezier(0.16,1,0.3,1) both',
                'scale-in': 'scale-in 0.6s cubic-bezier(0.16,1,0.3,1) both',
                'ken-burns': 'ken-burns 20s ease-out both',
            },
        },
    },
    plugins: [
        require('@tailwindcss/typography'),
    ],
};
