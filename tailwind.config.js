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
                // Decorative gold: borders, icon fills, background tints and
                // large shapes. At 2.96:1 on white it fails WCAG AA for text
                // at BOTH normal and large sizes — never use it for text on a
                // light background; use gold-deep instead.
                'luxury-gold': '#B8903A',
                // Text-safe gold for light backgrounds: 5.04:1 on white,
                // 4.73:1 on bg-soft — both clear AA for normal text. Do NOT
                // use it on dark-green (2.41:1); light-gold is the dark-
                // background token.
                'gold-deep': '#8A6A22',
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
