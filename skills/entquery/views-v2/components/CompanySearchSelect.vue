<template>
  <a-input :value="value" disabled placeholder="企业查询未包含在开源版中，请手动填写工商信息" />
</template>

<script setup>
defineProps({
  value: { type: String, default: '' },
})
defineEmits(['update:value', 'select'])
</script>
