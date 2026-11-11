<template>
  <a-select v-model:value="selected" show-search :filter-option="false"
    :placeholder="placeholder" allow-clear style="width:100%"
    @search="handleSearch" :loading="loading"
    :options="options" />
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { list } from '@/api/user'

const props = defineProps({
  modelValue: [String, Number],
  placeholder: { type: String, default: '选择用户...' },
  disabled: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])
const selected = ref(props.modelValue)
const loading = ref(false)
const options = ref([])

watch(() => props.modelValue, v => selected.value = v)
watch(selected, v => emit('update:modelValue', v))

async function handleSearch(kw) {
  if (!kw || kw.length < 1) { options.value = []; return }
  loading.value = true
  try {
    const res = await list({ keyword: kw })
    const data = res?.data?.list?.data || res?.data?.list || []
    options.value = data.map(u => ({
      value: u.store_user_id,
      label: u.real_name || u.user_name || String(u.store_user_id),
    }))
  } catch { options.value = [] }
  loading.value = false
}

// 初始加载：如果已有值，加载标签
onMounted(async () => {
  if (props.modelValue) {
    try {
      const res = await list({})
      const data = res?.data?.list?.data || res?.data?.list || []
      const u = data.find(u => u.store_user_id == props.modelValue)
      if (u) options.value = [{ value: u.store_user_id, label: u.real_name || u.user_name || String(u.store_user_id) }]
    } catch {}
  }
})
</script>
