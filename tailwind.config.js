import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    navy: '#0B1F4D',
                    'navy-deep': '#071536',
                    gold: '#C9A227',
                    'gold-light': '#E3C56A',
                    cream: '#F6EED8',
                    'cream-dark': '#EDE3C4',
                },
            },
            fontFamily: {
                sans: ['Source Sans 3', ...defaultTheme.fontFamily.sans],
                display: ['Oswald', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
