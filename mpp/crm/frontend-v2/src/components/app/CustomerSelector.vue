<template>
  <a-select v-model:value="selected" show-search :filter-option="false"
    :placeholder="placeholder" allow-clear style="width:100%"
    @search="handleSearch" :loading="loading"
    :options="options" />
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { getSelect } from '@/api/crm/customer'

const props = defineProps({
  modelValue: [String, Number],
  placeholder: { type: String, default: '搜索客户...' },
  disabled: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])
const selected = ref(props.modelValue)
const loading = ref(false)
const options = ref([])
let timer = null

watch(() => props.modelValue, v => selected.value = v)
watch(selected, v => emit('update:modelValue', v))

function handleSearch(kw) {
  if (timer) clearTimeout(timer)
  if (!kw || kw.length < 1) { options.value = []; return }
  timer = setTimeout(async () => {
    loading.value = true
    try {
      const res = await getSelect(kw)
      options.value = (res?.data?.list || []).map(c => ({
        value: c.id,
        label: `${c.customer_name} (${c.customer_code || c.id})`,
      }))
    } catch { options.value = [] }
    loading.value = false
  }, 300)
}

// 初始加载已有值的标签
onMounted(async () => {
  if (props.modelValue) {
    try {
      const res = await getSelect('')
      const c = (res?.data?.list || []).find(c => c.id == props.modelValue)
      if (c) options.value = [{ value: c.id, label: c.customer_name + ' (' + (c.customer_code || c.id) + ')' }]
    } catch {}
  }
})
</script>
