<template>
  <a-card :bordered="false" title="待审队列">
    <a-row :gutter="16" style="margin-bottom:16px">
      <a-col :span="8">
        <a-card size="small"><a-statistic title="待审核" :value="pagination.total" /></a-card>
      </a-col>
      <a-col :span="8">
        <a-card size="small"><a-statistic title="超 1 天未处理" :value="overdueCount" :value-style="{ color: overdueCount ? '#f5222d' : '' }" /></a-card>
      </a-col>
      <a-col :span="8">
        <a-card size="small"><a-statistic title="已指派" :value="assignedCount" /></a-card>
      </a-col>
    </a-row>

    <p style="color:#999;margin-bottom:12px">
      队列对审核组可见；只有被指派的人能通过或退回。
      <a @click="$router.push('/crm/project/review')">进入审核队列 →</a>
    </p>

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
        <template v-if="column.key === 'title'">
          <a @click="goProject(record)">{{ record.title || ('项目 #' + record.project_id) }}</a>
          <div style="color:#999;font-size:12px">{{ record.project_no }}</div>
        </template>
        <template v-if="column.key === 'waiting'">
          <span :style="{ color: record.waiting_seconds > 86400 ? '#f5222d' : '' }">{{ waitText(record.waiting_seconds) }}</span>
        </template>
        <template v-if="column.key === 'reviewer'">
          <span v-if="record.reviewer_user_name">{{ record.reviewer_user_name }}</span>
          <a-tag v-else>未指派</a-tag>
        </template>
      </template>
    </a-table>
  </a-card>
</template>

<script setup>
import { ref, reactive, computed, onActivated } from 'vue'
import { useRouter } from 'vue-router'
import { getReviewList } from '@/api/crm/project'

const router = useRouter()
const loading = ref(false)
const dataSource = ref([])
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const overdueCount = computed(() => dataSource.value.filter(r => Number(r.waiting_seconds) > 86400).length)
const assignedCount = computed(() => dataSource.value.filter(r => Number(r.reviewer_user_id) > 0).length)

const columns = [
  { title: '项目', key: 'title', width: 260 },
  { title: '送审人', dataIndex: 'submit_user_name', key: 'submit_user_name', width: 120 },
  { title: '送审时间', dataIndex: 'submit_time', key: 'submit_time', width: 130,
    customRender: ({ text }) => text ? new Date(text * 1000).toLocaleString() : '' },
  { title: '已等待', key: 'waiting', width: 110 },
  { title: '审核人', key: 'reviewer', width: 120 },
]

function waitText(seconds) {
  const s = Number(seconds || 0)
  if (s < 3600) return Math.floor(s / 60) + ' 分钟'
  if (s < 86400) return Math.floor(s / 3600) + ' 小时'
  return Math.floor(s / 86400) + ' 天'
}

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { result: 0, page: pagination.current, list_rows: pagination.pageSize }
    const res = await getReviewList(params)
    if (res?.status === 200) {
      dataSource.value = res.data?.list || []
      pagination.total = res.data?.total || 0
    }
  } finally { loading.value = false }
}

function onTableChange(pag) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  loadData()
}

function goProject(record) {
  router.push({ path: '/crm/project/detail', query: { id: record.project_id } })
}

loadData()
onActivated(() => loadData())
</script>
