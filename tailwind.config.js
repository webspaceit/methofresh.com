/**
 * Tailwind build config for the STOREFRONT only.
 *
 * This mirrors the inline `tailwind.config` that used to be injected by the
 * Tailwind Play CDN in app/Views/partials/wsit_head.php. Keeping it identical
 * guarantees the compiled stylesheet renders exactly like the runtime build.
 *
 * Admin views are intentionally NOT scanned here — the dashboard has its own
 * (larger) class surface and is not performance-critical.
 *
 * Rebuild after changing storefront markup:  npm run build:css
 */
/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './app/Views/layouts/wsit_main.php',
        './app/Views/partials/**/*.php',
        './app/Views/home/**/*.php',
        './app/Views/products/**/*.php',
        './app/Views/category/**/*.php',
        './app/Views/account/**/*.php',
        './app/Views/auth/**/*.php',
        './app/Views/cart/**/*.php',
        './app/Views/checkout/**/*.php',
        './app/Views/contact/**/*.php',
        './app/Views/pages/**/*.php',
        './app/Views/errors/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: [
                    'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont',
                    '"Segoe UI"', 'Roboto', '"Helvetica Neue"', 'Arial', 'sans-serif',
                ],
                bengali: ['"Noto Sans Bengali"', 'sans-serif'],
                bangla: ['"Hind Siliguri"', '"Noto Sans Bengali"', 'sans-serif'],
            },
            colors: {
                brand: {
                    50: '#F4F7F4',
                    100: '#E3EBE3',
                    200: '#C9D8C6',
                    300: '#A3BCA4',
                    400: '#6F9176',
                    500: '#4C7A5C',
                    600: '#2D6A4F',
                    700: '#1B4332',
                    800: '#143626',
                    900: '#0B1F16',
                    950: '#08170F',
                },
                honey: {
                    50: '#fef9ea',
                    100: '#f8dc90',
                    200: '#f5ce6c',
                    300: '#f2a635',
                    400: '#e08b1f',
                    500: '#c6711a',
                    600: '#a35715',
                    700: '#7c4213',
                    800: '#59300f',
                    900: '#3b200a',
                },
                cream: '#FAF7F2',
                warmBeige: '#F3EFE6',
                card: '#ffffff',
            },
        },
    },
    plugins: [],
};
