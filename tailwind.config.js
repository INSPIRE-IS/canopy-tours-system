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
                publicSans: ['PublicSans', 'sans-serif'],

              },
              fontSize: {
                xs: ['12px', { lineHeight: '18px', letterSpacing: '-0.24px' }],
                // add more sizes if needed
              },
              boxShadow: {
                soft: '0px 1px 2px 0px rgba(82, 88, 102, 0.06)',
                medium: '0px 4px 30px 0px rgba(26, 28, 33, 0.05)',
              },
              colors: {
                'dark-gray': '#8C8C95',
                'light-gray': '#8C8C95',
                'other-gray-bg': '#F9F9FB',
                'dark-green': '#31B700',
                'light-green': '#D6FBE6',
                'cal-pink': '#FCE4DF',
                'cal-green': '#D6FBE6',
                'cal-bauge': '#FDF0DB',
                'cal-select': '#55FF9E',
                'primary-3': '#F3F4F6', 
                'primary-5': '#000000',   
                'primary-0': '#FFFFFF',              
                'primary-8': '#EFEFEF',              
                'primary-10': '#F8FBFF',
                'primary-11': '#D8DADF',
                'primary-13': '#C9C9D4',
                'primary-14': '#8C8C95',
                'primary-4': '#55FF9E',
                'primary-12': '#D2D2DC',
                'primary-7': '#4D81E7',
                'primary-1': '#FDF0DB',
                'primary-2': '#C4C4D0',
                'primary-17': '#e2e4e9-30%',
                'primary-16': '#FF8000',
                'primary-19': '#FF8000=40%',
                'primary-18': '#8BABA0',
                'primary-15': '#DE3B39', 
                'primary-20': '#F9F9FB',               
                'primary-6': '#0B0B0B',
                'primary-21': '#258EF0',
                'primary-22': '#31b7001a',
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
