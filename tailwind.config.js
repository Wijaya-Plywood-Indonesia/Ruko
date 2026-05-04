// 1. Impor preset Filament di bagian atas
import preset from "./vendor/filament/filament/tailwind.config.js";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        // Tambahkan baris ini untuk memastikan Filament Page terbaca
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    darkMode: 'class', // Penting agar dark: class berfungsi
    theme: {
        extend: {
            colors: {
                // Opsional: Jika Anda ingin mendefinisikan warna amber kustom
            },
            borderRadius: {
                // Opsional: Jika ingin membuat 4px sebagai standar
                'accounting': '4px',
            }
        },
    },
    plugins: [],
}