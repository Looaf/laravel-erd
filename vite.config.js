import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    build: {
        outDir: 'public/vendor/laravel-erd',
        rollupOptions: {
            input: 'resources/js/app.tsx',
            output: {
                entryFileNames: 'js/app.js',
                chunkFileNames: 'js/[name].js',
                assetFileNames: 'css/app.css'
            }
        },
        emptyOutDir: true
    },
    publicDir: false // Disable public directory copying to avoid conflicts
});