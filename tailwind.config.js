import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.ts',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                coin: {
                    primary: '#7C3AED',
                    accent: '#A855F7',
                    success: '#10B981',
                    danger: '#EF4444',
                    warning: '#F59E0B',
                    'dark-bg': '#0F0F1A',
                    'dark-card': '#1A1A2E',
                },
            },
        },
    },

    plugins: [forms],
};
