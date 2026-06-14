<template>
  <div class="placeholder-page">
    <a-result title="页面开发中" sub-title="该页面将从旧项目迁移过来">
      <template #icon>
        <tool-outlined />
      </template>
      <template #extra>
        <a-tag color="orange">{{ route.meta?.title || route.path }}</a-tag>
      </template>
    </a-result>
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { ToolOutlined } from '@ant-design/icons-vue'
const route = useRoute()
</script>

<style scoped>
.placeholder-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 400px;
}
</style>
