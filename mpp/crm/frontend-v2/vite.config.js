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
      vue: path.resolve(__dirname, 'node_modules/vue/dist/vue.runtime.esm-bundler.js'),
      'vue-router': path.resolve(__dirname, 'node_modules/vue-router/dist/vue-router.mjs'),
      'ant-design-vue': path.resolve(__dirname, 'node_modules/ant-design-vue'),
      '@ant-design/icons-vue': path.resolve(__dirname, 'node_modules/@ant-design/icons-vue'),
      pinia: path.resolve(__dirname, 'node_modules/pinia/dist/pinia.mjs'),
      dayjs: path.resolve(__dirname, 'node_modules/dayjs'),
      echarts: path.resolve(__dirname, 'node_modules/echarts'),
    },
  },
  server: {
    port: 9999,
    host: '0.0.0.0',
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
