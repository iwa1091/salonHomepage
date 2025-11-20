import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    server: {
        host: true,
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
        },
    },

    // ⭐ public ディレクトリを無効化（Vite 管理外にする）
    publicDir: false,

    resolve: {
        alias: {
            // ⭐ resources を @ で参照できるようにする
            '@': path.resolve(__dirname, 'resources'),
        },
    },

    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.jsx',
            ],
            refresh: true,
        }),
        react(),
    ],
});
