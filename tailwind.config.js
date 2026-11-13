/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './index.html',
        './src/**/*.{js,jsx}',
        './admin/**/*.{html,js}'
    ],

    theme: {
        extend: {
            colors: {
                sage: {
                    50: '#f4f6ef',
                    100: '#e8ecdf',
                    200: '#d4ddc7',
                    300: '#b7bea0',
                    500: '#87936c',
                    600: '#707d57',
                    700: '#596547'
                },
                ink: '#292d25'
            },

            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif']
            },

            boxShadow: {
                soft: '0 12px 35px rgba(41,45,37,.06)'
            }
        }
    },

    plugins: []
};