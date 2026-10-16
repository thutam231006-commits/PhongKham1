/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        mg: {
          primary: '#1D4B43',        // Deep Teal
          'primary-hover': '#14352F',
          secondary: '#709782',      // Sage Green
          'secondary-light': '#E8F0EC',
          bg: '#F7FAFC',             // Warm Off-White
          surface: '#FFFFFF',
          text: '#1A202C',
          'text-muted': '#4A5568',
        }
      },
      fontFamily: {
        sans: ['Inter', 'Plus Jakarta Sans', 'sans-serif'],
      }
    },
  },
  plugins: [],
}