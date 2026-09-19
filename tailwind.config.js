import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        // Category tag colours — generated dynamically in Blade, must be safelisted
        'bg-blue-600', 'bg-red-600', 'bg-emerald-600', 'bg-violet-600',
        'bg-orange-500', 'bg-rose-500', 'bg-cyan-600', 'bg-green-600', 'bg-stone-600',
        'bg-fuchsia-600',
        // Avatar background colours
        'bg-teal-600', 'bg-amber-500', 'bg-indigo-600',
    ],

    theme: {
        extend: {
            colors: {
                novara: {
                    300: '#7ea1c9',
                    400: '#5f85b2',
                    500: '#3f6c9f',
                    600: '#2f5682',
                    700: '#24486d',
                    800: '#1e3a5f',
                    900: '#152a45',
                },
                brand: {
                    DEFAULT: '#1e3a5f',
                    hover: '#152a45',
                    soft: '#dbe7f5',
                },
                surface: {
                    subtle: '#fafaf9',
                    muted: '#f5f5f4',
                },
            },
            fontFamily: {
                sans: ['"Source Sans 3"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                serif: ['"Source Serif 4"', 'Georgia', 'Cambria', 'Times New Roman', 'serif'],
            },
            keyframes: {
                ticker: {
                    '0%':   { transform: 'translateX(0)' },
                    '100%': { transform: 'translateX(-50%)' },
                },
            },
            animation: {
                ticker: 'ticker 50s linear infinite',
            },
        },
    },

    plugins: [forms],
};
