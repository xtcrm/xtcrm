<template>
  <div class="empty-state">
    <div class="empty-icon">
      <component :is="icons[type] || icons.default" />
    </div>
    <div class="empty-title">{{ title }}</div>
    <div class="empty-desc" v-if="description">{{ description }}</div>
    <div class="empty-action" v-if="$slots.default">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { InboxOutlined, SearchOutlined, FileTextOutlined, TeamOutlined } from '@ant-design/icons-vue'

defineProps({
  type: { type: String, default: 'default' },
  title: { type: String, default: '暂无数据' },
  description: { type: String, default: '' },
})

const icons = {
  default: InboxOutlined,
  search: SearchOutlined,
  file: FileTextOutlined,
  team: TeamOutlined,
}
</script>

<style scoped>
.empty-state {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 48px 24px; text-align: center;
}
.empty-icon { font-size: 48px; color: #d9d9d9; margin-bottom: 16px; }
.empty-title { font-size: 14px; color: #999; font-weight: 500; margin-bottom: 8px; }
.empty-desc { font-size: 12px; color: #bbb; max-width: 300px; line-height: 1.6; margin-bottom: 16px; }
.empty-action { margin-top: 8px; }
</style>
