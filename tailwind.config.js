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
          primary: '#2D82C4',        // Deep Teal
          'primary-hover': '#23689C',
          secondary: '#6FA6CF',      // Sage Green
          'secondary-light': '#EAF4FB',
          bg: '#F5FAFE',             // Warm Off-White
          surface: '#FFFFFF',
          text: '#1A202C',
          'text-muted': '#4A5568',
        }
      },
      fontFamily: {
        sans: ['Inter', 'Arial', 'sans-serif'],
      }
    },
  },
  plugins: [],
}
