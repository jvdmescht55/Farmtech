/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                farmtech: {
                    green: '#1F5C3F',
                    'green-dark': '#123B29',
                    gold: '#C99A3B',
                    cream: '#FAF7F0',
                },
            },
        },
    },
    plugins: [],
};
