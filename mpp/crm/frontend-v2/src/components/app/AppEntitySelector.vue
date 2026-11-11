<template>
  <a-select v-model:value="selected" show-search :filter-option="false"
    :placeholder="placeholder" allow-clear style="width:100%"
    @search="handleSearch" :loading="loading" :options="options"
    :disabled="disabled" />
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import request from '@/utils/request'

const props = defineProps({
  modelValue: [String, Number],
  appId: { type: String, default: '' },
  entity: { type: String, required: true },   // 应用内实体 key，如 supplier
  labelField: { type: String, default: 'name' },  // 显示字段
  placeholder: { type: String, default: '选择...' },
  disabled: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])
const selected = ref(props.modelValue)
const loading = ref(false)
const options = ref([])
let timer = null

watch(() => props.modelValue, v => selected.value = v)
watch(selected, v => emit('update:modelValue', v))

async function fetchOptions(kw = '') {
  loading.value = true
  try {
    const res = await request({ url: '/crm.appEngine/dataList', method: 'get', params: {
      app_id: props.appId, entity_key: props.entity,
      pageSize: 50, search: kw || undefined,
    }})
    const list = res?.data?.list || []
    options.value = list.map(r => {
      const label = r[props.labelField] || r.id || r.name
      return { value: r.id, label: String(label) }
    })
  } catch { options.value = [] }
  loading.value = false
}

function handleSearch(kw) {
  if (timer) clearTimeout(timer)
  timer = setTimeout(() => fetchOptions(kw), 300)
}

onMounted(() => { fetchOptions() })
</script>
