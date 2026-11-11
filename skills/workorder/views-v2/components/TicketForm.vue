<template>
  <a-modal :title="mdl && mdl.id ? '编辑工单' : '新建工单'" :open="open" :footer="null" @cancel="close">
    <a-alert
      type="warning"
      show-icon
      message="工单功能未包含在开源版中"
      description="本仓库只开源 CRM 部分。创建/编辑工单需要 workorder skill，请安装闭源 skill 后再使用。"
    />
    <div style="text-align:right;margin-top:16px">
      <a-button type="primary" @click="close">知道了</a-button>
    </div>
  </a-modal>
</template>

<script setup>
const props = defineProps({
  open: Boolean,
  mdl: Object,
  typeOptions: Array,
  preset: Object,
})
const emit = defineEmits(['update:open', 'ok'])

function close() {
  emit('update:open', false)
}
</script>
