<template>
  <a-config-provider :theme="theme" :locale="zhCN">
    <router-view />
    <AiAssistant />
  </a-config-provider>
</template>

<script setup>
import zhCN from 'ant-design-vue/es/locale/zh_CN'
import AiAssistant from '@/components/AiAssistant/index.vue'

const theme = { token: { colorPrimary: '#d6893b', borderRadius: 4, colorLink: '#bc791c' } }
</script>
