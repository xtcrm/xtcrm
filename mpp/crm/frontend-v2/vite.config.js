import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'
import path from 'node:path'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
      '@skills': fileURLToPath(new URL('../../../skills', import.meta.url)),
      '@platform': fileURLToPath(new URL('../../../app/platform', import.meta.url)),
      vue: path.resolve(__dirname, 'node_modules/vue/dist/vue.runtime.esm-bundler.js'),
      'vue-router': path.resolve(__dirname, 'node_modules/vue-router/dist/vue-router.mjs'),
      'ant-design-vue': path.resolve(__dirname, 'node_modules/ant-design-vue'),
      '@ant-design/icons-vue': path.resolve(__dirname, 'node_modules/@ant-design/icons-vue'),
      pinia: path.resolve(__dirname, 'node_modules/pinia/dist/pinia.mjs'),
      dayjs: path.resolve(__dirname, 'node_modules/dayjs'),
      echarts: path.resolve(__dirname, 'node_modules/echarts'),
      '@vue-flow/core': path.resolve(__dirname, 'node_modules/@vue-flow/core'),
      '@vue-flow/background': path.resolve(__dirname, 'node_modules/@vue-flow/background'),
      '@vue-flow/controls': path.resolve(__dirname, 'node_modules/@vue-flow/controls'),
      '@vue-flow/minimap': path.resolve(__dirname, 'node_modules/@vue-flow/minimap'),
    },
  },
  server: {
    port: 9999,
    host: '0.0.0.0',
    strictPort: true,
    // 本地固定域名：crm.dev.xtocn.com（nginx 反代到 9999）
    allowedHosts: true,
    proxy: {
      '/crm.ai': {
        target: 'http://127.0.0.1',
        changeOrigin: true,
        rewrite: (path) => '/index.php?s=/platform' + path,
      },
      '/crm.agent': {
        target: 'http://127.0.0.1',
        changeOrigin: true,
        rewrite: (path) => '/index.php?s=/platform' + path,
      },
    },
  },
  clearScreen: false,   // Tauri: 保留终端日志可见
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
