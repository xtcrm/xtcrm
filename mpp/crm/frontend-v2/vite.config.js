import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'
import path from 'node:path'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

export default defineConfig({
  base: '/crm/',
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
      '@skills': fileURLToPath(new URL('./src/skills', import.meta.url)),
      vue: path.resolve(__dirname, 'node_modules/vue/dist/vue.runtime.esm-bundler.js'),
      'vue-router': path.resolve(__dirname, 'node_modules/vue-router/dist/vue-router.mjs'),
      'ant-design-vue': path.resolve(__dirname, 'node_modules/ant-design-vue'),
      '@ant-design/icons-vue': path.resolve(__dirname, 'node_modules/@ant-design/icons-vue'),
      pinia: path.resolve(__dirname, 'node_modules/pinia/dist/pinia.mjs'),
      dayjs: path.resolve(__dirname, 'node_modules/dayjs'),
    },
  },
  server: {
    port: 9999,
    host: '0.0.0.0',
    proxy: {
      '/index.php': {
        target: 'http://crm.dev.xtocn.com',
        changeOrigin: true,
      },
      '/uploads': {
        target: 'http://crm.dev.xtocn.com',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: '../../../public/crm',
    emptyOutDir: true,
  },
  css: {
    preprocessorOptions: {
      less: { javascriptEnabled: true },
    },
  },
})
