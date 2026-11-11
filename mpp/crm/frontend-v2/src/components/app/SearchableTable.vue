<template>
  <div>
    <a-row :gutter="12" style="margin-bottom:16px">
      <a-col :span="8">
        <a-input v-model:value="kw" :placeholder="searchPlaceholder" allow-clear @press-enter="search" />
      </a-col>
      <a-col :span="4">
        <a-button type="primary" @click="search">查询</a-button>
        <slot name="actions" />
      </a-col>
    </a-row>
    <a-table :columns="allColumns" :data-source="list" :loading="loading"
      :pagination="pagination" @change="onChange" row-key="id" size="small">
      <template #bodyCell="scope">
        <slot name="bodyCell" v-bind="scope" />
      </template>
    </a-table>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import request from '@/utils/request'

const props = defineProps({
  appId: { type: String, required: true },
  entity: { type: String, required: true },
  columns: { type: Array, default: () => [] },
  searchPlaceholder: { type: String, default: '搜索...' },
  defaultSort: { type: String, default: 'id' },
})
const kw = ref('')
const list = ref([])
const loading = ref(false)
const pagination = reactive({ current: 1, pageSize: 20, total: 0, showSizeChanger: true })

const allColumns = [...props.columns, { title: '操作', key: 'action', width: 150, fixed: 'right' }]

function search() { loadData(true) }

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const res = await request({ url: '/crm.appEngine/dataList', method: 'get', params: {
      app_id: props.appId, entity_key: props.entity,
      page: pagination.current, pageSize: pagination.pageSize,
      search: kw.value || undefined, sort: props.defaultSort, order: 'desc',
    }})
    const d = res?.data || {}
    list.value = d.list || []
    pagination.total = d.total || 0
  } finally { loading.value = false }
}

function onChange(pag) { pagination.current = pag.current; pagination.pageSize = pag.pageSize; loadData() }

onMounted(() => loadData())
defineExpose({ loadData, list })
</script>
