import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Filament/**/*.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    
    safelist: [
        // Preserve dynamic Filament classes
        'bg-primary-50',
        'bg-primary-100',
        'bg-primary-500',
        'bg-primary-600',
        'text-primary-500',
        'text-primary-600',
        'border-primary-300',
        'ring-primary-500',
        // Preserve common state classes
        'bg-success-50',
        'bg-success-500',
        'bg-danger-50',
        'bg-danger-500',
        'bg-warning-50',
        'bg-warning-500',
        'text-success-600',
        'text-danger-600',
        'text-warning-600',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
