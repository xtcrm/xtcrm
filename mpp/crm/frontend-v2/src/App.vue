<template>
  <a-config-provider :theme="theme" :locale="zhCN">
    <router-view />
  </a-config-provider>
</template>

<script setup>
import zhCN from 'ant-design-vue/es/locale/zh_CN'

const theme = { token: { colorPrimary: '#d6893b', borderRadius: 4, colorLink: '#bc791c' } }
</script>
