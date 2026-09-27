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

                    if (id.includes('react') || id.includes('react-dom') || id.includes('react-router')) {
                        return 'react-vendor';
                    }

                    if (id.includes('qrcode') || id.includes('jsqr') || id.includes('jsbarcode')) {
                        return 'scanner-vendor';
                    }

                    if (id.includes('date-fns')) {
                        return 'date-vendor';
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
