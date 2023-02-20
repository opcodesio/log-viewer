const colors = require('tailwindcss/colors')

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        brand: colors.sky,
        gray: colors.zinc,
      },
      width: {
        '88': '22rem',
      },
      padding: {
        '88': '22rem',
      },
      transitionProperty: {
        'width': 'width',
      },
    },
  },
  plugins: [],
}
