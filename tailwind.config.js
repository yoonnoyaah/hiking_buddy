/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: { extend: {
    colors: { sage: { 50:'#f6f7f2',100:'#e9ebdf',300:'#b7bea0',500:'#858d68',600:'#737a57',700:'#5c6346' }, ink:'#22251f' },
    fontFamily: { sans:['Inter','ui-sans-serif','system-ui','sans-serif'] },
    boxShadow: { soft:'0 12px 35px rgba(34,37,31,.08)' }
  }},
  plugins: []
}