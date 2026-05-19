// 1. Impor preset resmi Filament v3 dengan path yang benar
import preset from './vendor/filament/support/tailwind.config.preset'

/** @type {import('tailwindcss').Config} */
export default {
    // 2. WAJIB DAFTARKAN PRESET DI SINI agar sistem warna Filament aktif
    presets: [preset],

    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {},
            borderRadius: {
                'accounting': '4px',
            }
        },
    },
    plugins: [],
}