<template>
  <a-row :gutter="16">
    <a-col v-for="item in stats" :key="item.key" :span="Math.floor(24 / stats.length)">
      <a-card size="small">
        <a-statistic :title="item.label" :value="item.value"
          :precision="item.precision ?? 0"
          :prefix="item.prefix"
          :value-style="item.color ? { color: item.color } : {}" />
      </a-card>
    </a-col>
  </a-row>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import request from '@/utils/request'

const props = defineProps({
  appId: { type: String, required: true },
  entity: { type: String, required: true },
  items: { type: Array, default: () => [] },
  autoLoad: { type: Boolean, default: true },
})
const stats = ref([])

function buildStats(rawList) {
  stats.value = props.items.map(item => {
    let val = 0
    if (typeof item === 'string') {
      val = rawList.length
    } else if (item.filter) {
      let filters = {}
      if (typeof item.filter === 'string') {
        const [k, v] = item.filter.split('=')
        filters[k] = v
      } else if (typeof item.filter === 'object') {
        filters = item.filter
      }
      val = rawList.filter(r => Object.entries(filters).every(([k,v]) => r[k] == v)).length
    } else {
      val = rawList.length
    }
    return { key: item.key || item, label: item.label || item, value: val, color: item.color, precision: item.precision, prefix: item.prefix }
  })
}

async function load() {
  try {
    const res = await request({ url: '/crm.appEngine/dataList', method: 'get', params: {
      app_id: props.appId, entity_key: props.entity, pageSize: 9999
    }})
    buildStats(res?.data?.list || [])
  } catch {}
}

onMounted(() => { if (props.autoLoad && props.items.length) load() })
watch(() => props.items, () => { if (props.items.length) load() })
</script>
