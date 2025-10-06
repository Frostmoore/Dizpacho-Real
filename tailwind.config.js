import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import lineClamp from '@tailwindcss/line-clamp'; // ⬅️ AGGIUNGI QUESTA LINEA

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
        sans: ['Figtree', ...defaultTheme.fontFamily.sans],
      },
      colors: {
        // ⬅️ AGGIUNGI QUESTO BLOCCO
        whatsapp: {
          DEFAULT: '#25D366',
            50: '#E9FFF1',
            100: '#CFFFE2',
            200: '#A0FEC5',
            300: '#70FCA8',
            400: '#41FA8B',
            500: '#25D366',
            600: '#1EAA53',
            700: '#168040',
            800: '#0F572D',
            900: '#083D20',
        },
      },
    },
  },

  plugins: [
    forms,
    lineClamp, // ⬅️ AGGIUNGI QUESTA RIGA
  ],
};
