<template>
  <a-select v-model:value="selected" show-search :filter-option="false"
    :placeholder="placeholder" allow-clear style="width:100%"
    @search="handleSearch" :loading="loading"
    :options="options" />
</template>

<script setup>
import { ref, watch } from 'vue'
import { getList } from '@/api/crm/contact'

const props = defineProps({
  modelValue: [String, Number],
  placeholder: { type: String, default: '搜索联系人...' },
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
      const res = await getList({ keyword: kw, list_rows: 30 })
      options.value = (res?.data?.list || []).map(c => ({
        value: c.id,
        label: `${c.contact_name} — ${(c.company_names || [])[0] || ''}`,
      }))
    } catch { options.value = [] }
    loading.value = false
  }, 300)
}
</script>
