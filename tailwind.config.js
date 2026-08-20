/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                // Deep ag-tech green (brand/primary) + crisp mint (accent/CTA) +
                // amber (inventory alerts, ICASA/compliance). Neutrals lean on
                // Tailwind's built-in slate scale directly in views.
                brand: {
                    500: '#2D7350',
                    600: '#1F5A3E',
                    700: '#1A4D35',
                    800: '#163F2B',
                    900: '#143D2B',
                    950: '#0B2A1C',
                },
                mint: {
                    DEFAULT: '#10B981',
                    dark: '#059669',
                    light: '#6EE7B7',
                },
                alert: {
                    DEFAULT: '#F59E0B',
                    dark: '#B45309',
                    light: '#FCD34D',
                },
                // Aliases so admin views (kept visually simpler/utilitarian on
                // purpose) still resolve to the same real palette above.
                farmtech: {
                    green: '#143D2B',
                    'green-dark': '#0B2A1C',
                    gold: '#F59E0B',
                    cream: '#F8FAFC',
                },
            },
            fontFamily: {
                display: ['"Plus Jakarta Sans"', '"Inter"', 'sans-serif'],
                body: ['"Inter"', 'sans-serif'],
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
            },
            animation: {
                'reveal-up': 'reveal-up 0.6s cubic-bezier(0.16,1,0.3,1) both',
                'stamp-in': 'stamp-in 0.5s cubic-bezier(0.34,1.56,0.64,1) both',
                'ticker': 'ticker 22s linear infinite',
                'pop-in': 'pop-in 0.4s cubic-bezier(0.34,1.56,0.64,1) both',
                'toast-in': 'toast-in 0.3s cubic-bezier(0.16,1,0.3,1) both',
                'glow-pulse': 'glow-pulse 2.2s ease-out infinite',
            },
        },
    },
    plugins: [
        require('@tailwindcss/typography'),
    ],
};
