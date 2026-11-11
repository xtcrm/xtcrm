<template>
  <CustomerForm mode="page" :mdl="mdl" @ok="router.push('/crm/customer/index')" @update:open="router.push('/crm/customer/index')" />
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getDetail } from '@/api/crm/customer'
import { useAiStore } from '@/stores/ai'
import CustomerForm from './components/CustomerForm.vue'

const route = useRoute()
const router = useRouter()
const mdl = ref(null)
const aiStore = useAiStore()

// AI 解析公司信息/抓取网页后自动填入表单
watch(() => aiStore.formFill, (val) => {
  if (val?.data) {
    const d = val.data
    if (!mdl.value) mdl.value = {}
    Object.keys(d).forEach(k => { if (d[k]) mdl.value[k] = d[k] })
    mdl.value = { ...mdl.value }
  }
})

onMounted(() => {
  const id = route.query.id
  if (id) {
    getDetail(id).then(res => {
      mdl.value = res?.data?.detail || res?.data || null
    })
  }
})
</script>
