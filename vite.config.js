import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css'
            ],
            refresh: true,
        }),
    ],
    server: {
        hmr: {
            host: 'catapult.roguespy.co',
        },
        host: '0.0.0.0',
        port: 5173,
    },
    build: {
        // Ensure assets are built with the correct base URL
        manifest: 'manifest.json',
        outDir: 'public/build',
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['alpinejs'],
                },
                // Optimize chunk size
                chunkFileNames: 'assets/js/[name]-[hash].js',
                entryFileNames: 'assets/js/[name]-[hash].js',
                assetFileNames: 'assets/[ext]/[name]-[hash].[ext]',
            },
        },
        // Enable asset minification
        minify: true,
        // Target modern browsers for smaller bundle size
        target: 'es2018',
        // Split chunks for better caching
        cssCodeSplit: true,
        // Add source maps for better debugging
        sourcemap: process.env.NODE_ENV === 'development',
        // Enable asset compression
        assetsInlineLimit: 4096,
    },
});
