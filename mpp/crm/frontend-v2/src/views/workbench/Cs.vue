<template>
  <a-card :bordered="false" title="我的服务客户">
    <a-row :gutter="16" style="margin-bottom:16px">
      <a-col :span="8">
        <a-card size="small"><a-statistic title="我服务的客户" :value="pagination.total" /></a-card>
      </a-col>
      <a-col :span="8">
        <a-card size="small"><a-statistic title="7 天未跟进" :value="needFollow" /></a-card>
      </a-col>
      <a-col :span="8">
        <a-card size="small"><a-statistic title="本页客户数" :value="dataSource.length" /></a-card>
      </a-col>
    </a-row>

    <a-form layout="inline" style="margin-bottom:16px">
      <a-form-item label="客户">
        <a-input v-model:value="queryParam.keyword" placeholder="名称 / 编码 / 电话" allow-clear />
      </a-form-item>
      <a-form-item>
        <a-button type="primary" @click="loadData(true)">查询</a-button>
      </a-form-item>
    </a-form>

    <a-table
      :columns="columns"
      :data-source="dataSource"
      :loading="loading"
      :pagination="pagination"
      :row-key="r => r.id"
      size="middle"
      @change="onTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'customer_name'">
          <a @click="goDetail(record)">{{ record.customer_name }}</a>
        </template>
        <template v-if="column.key === 'last_followup_time'">
          <span :style="{ color: staleDays(record) > 7 ? '#f5222d' : '' }">{{ followText(record) }}</span>
        </template>
      </template>
    </a-table>
  </a-card>
</template>

<script setup>
import { ref, reactive, computed, onActivated } from 'vue'
import { useRouter } from 'vue-router'
import { getList } from '@/api/crm/customer'

const router = useRouter()
const loading = ref(false)
const dataSource = ref([])
const queryParam = reactive({})
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const needFollow = computed(() => dataSource.value.filter(r => staleDays(r) > 7).length)

const columns = [
  { title: '客户名称', key: 'customer_name', width: 220 },
  { title: '行业', dataIndex: 'industry', key: 'industry', width: 120 },
  { title: '等级', dataIndex: 'level_name', key: 'level_name', width: 100 },
  { title: '最近跟进', key: 'last_followup_time', width: 140 },
  { title: '状态', dataIndex: 'status_text', key: 'status_text', width: 100 },
]

function staleDays(record) {
  if (!record.last_followup_time) return 999
  return Math.floor((Date.now() / 1000 - Number(record.last_followup_time)) / 86400)
}

function followText(record) {
  const d = staleDays(record)
  return d >= 999 ? '从未跟进' : `${d} 天前`
}

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { scope: 'cs', page: pagination.current, list_rows: pagination.pageSize, ...queryParam }
    const res = await getList(params)
    if (res?.status === 200) {
      const list = res.data?.list || {}
      dataSource.value = list.data || []
      pagination.total = list.total || 0
    }
  } finally { loading.value = false }
}

function onTableChange(pag) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  loadData()
}

function goDetail(record) {
  router.push({ path: '/crm/customer/detail', query: { id: record.id, from: 'cs' } })
}

loadData()
onActivated(() => loadData())
</script>
