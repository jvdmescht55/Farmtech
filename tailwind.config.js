/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                // Field & instrument palette — khaki/steel/manifest-paper, with a
                // safety-tag orange accent (calibration-tag / inspection-sticker
                // territory, not a generic terracotta).
                field: {
                    950: '#141810',
                    900: '#1E2418',
                    800: '#2B3323',
                    700: '#3C4830',
                    600: '#4F5E3F',
                },
                steel: {
                    100: '#EDEDE8',
                    300: '#C9CABE',
                    500: '#8B8C82',
                    700: '#5C5D54',
                },
                tag: {
                    // Safety-orange, like an inspection/calibration tag.
                    DEFAULT: '#C6560E',
                    dark: '#9C420A',
                    light: '#F0985A',
                },
                paper: '#F3EFE4',
                // Aliases so admin views (kept visually simpler/utilitarian on
                // purpose) still resolve to the same real palette above.
                farmtech: {
                    green: '#3C4830',
                    'green-dark': '#1E2418',
                    gold: '#C6560E',
                    cream: '#F3EFE4',
                },
            },
            fontFamily: {
                display: ['"Space Grotesk"', 'sans-serif'],
                body: ['"IBM Plex Sans"', 'sans-serif'],
                mono: ['"IBM Plex Mono"', 'monospace'],
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
                'scanline': {
                    '0%': { backgroundPosition: '0 0' },
                    '100%': { backgroundPosition: '0 24px' },
                },
            },
            animation: {
                'reveal-up': 'reveal-up 0.6s cubic-bezier(0.16,1,0.3,1) both',
                'stamp-in': 'stamp-in 0.5s cubic-bezier(0.34,1.56,0.64,1) both',
                'ticker': 'ticker 22s linear infinite',
            },
        },
    },
    plugins: [
        require('@tailwindcss/typography'),
    ],
};
