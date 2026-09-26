import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return undefined;
                    }
                    if (id.includes('pusher-js') || id.includes('laravel-echo')) {
                        return 'realtime-vendor';
                    }

                    return 'vendor';
                },
            },
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/js/frontend/main.tsx',
                'resources/js/admin/main.tsx',
            ],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
});
