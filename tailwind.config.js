import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                inter: ['Inter', 'sans-serif'],
                geist: ['Geist', 'sans-serif'],
              },
              fontSize: {
                xs: ['12px', { lineHeight: '18px', letterSpacing: '-0.24px' }],
                // add more sizes if needed
              },
              colors: {
                'dark-gray': '#8C8C95',
                'light-gray': '#8C8C95',
                'other-gray-bg': '#F9F9FB',
                'gts-dark-green': '#5E7306',
                'gts-light-green': 'rgba(184, 229, 13, 0.25)',
                'primary-3': '#F3F4F6', 
                'primary-5': '#000000',   
                'primary-0': '#FFFFFF',              
                'primary-8': '#EFEFEF',              
                'primary-10': '#F8FBFF',
                'primary-11': '#D8DADF',
                'primary-13': '#C9C9D4',
                'primary-14': '#8C8C95',
                'primary-4': '#B8E50C',
                'primary-12': '#D2D2DC',
                'primary-7': 'rgba(184, 229, 13, 0.25)',
                'primary-1': '#AF9494',
                'primary-2': '#C4C4D0',
                'primary-17': '#e2e4e9-30%',
                'primary-16': '#FF8000',
                'primary-19': '#FF8000=40%',
                'primary-18': '#8BABA0',
                'primary-15': '#DE3B39', 
                'primary-20': '#F9F9FB',               
                'primary-6': '#0B0B0B',
                'primary-21': '#258EF0',
                'primary-22': '#8DCB3D',
                'primary-23': '#B8E50D',
                'primary-24': '#b6e50d41',
                
                'secondary-100': '#F1F1F5',
                'secondary-200': '#E5E5EC',
                'secondary-300': '#BEBEC8',
                'secondary-400': '#5B5A64',
                'secondary-500': '#44444A',
                'secondary-600': '#252528',
                'secondary-700': '#161618',
                'gray-menu-icon': '#8C8C95',
                'black-menu-icon': '#0B0B0B',
  
              },
        },
    },

    plugins: [forms],
};
