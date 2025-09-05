/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.jsx",
    "./resources/**/*.ts",
    "./resources/**/*.tsx",
  ],
  theme: {
    extend: {
      colors: {
        'erd-primary': '#3b82f6',
        'erd-secondary': '#64748b',
        'erd-accent': '#10b981',
      }
    },
  },
  plugins: [],
}