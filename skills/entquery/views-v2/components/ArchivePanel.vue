<template>
  <!-- 开源版不展示工商档案；客户详情只在租户开启 entquery 时才渲染本组件 -->
  <div></div>
</template>

<script setup>
defineProps({
  creditCode: { type: String, default: '' },
  companyName: { type: String, default: '' },
  title: { type: String, default: '工商档案' },
})
defineEmits(['loaded'])
</script>
