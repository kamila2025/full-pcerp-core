import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import Icons from 'unplugin-icons/vite'
import VueI18nPlugin from '@intlify/unplugin-vue-i18n/vite'
// https://lucide.dev/icons/

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
        VueI18nPlugin({
            include: 'resources/locales/**',
        }),
        Icons(),
    ],
});
