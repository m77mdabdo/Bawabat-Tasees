import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['IBM Plex Sans Arabic', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'primary-green': '#0B5D3B',
                'dark-green': '#073E2A',
                'luxury-gold': '#B8903A',
                'light-gold': '#D4B76A',
                'bg-soft': '#F7F8F6',
                'text-main': '#17211C',
                'text-secondary': '#66706A',
                'border-default': '#E5E9E6',
            },
        },
    },

    plugins: [forms],
};
