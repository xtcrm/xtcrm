<template>
  <tab-page-view />
</template>

<script setup>
// 分组路由的占位组件。keep-alive 的缓存必须发生在【叶子页面】这一层，
// 所以这里交给 TabPageView 处理 —— 详见该文件顶部注释。
import TabPageView from '@/layouts/TabPageView.vue'
</script>
