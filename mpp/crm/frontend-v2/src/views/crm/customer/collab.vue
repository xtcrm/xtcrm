<template>
  <a-card :bordered="false" class="collab-page">
    <!-- 总览 -->
    <a-row :gutter="16" class="stats-row">
      <a-col :span="8">
        <div class="stat-card">
          <div class="stat-num">{{ counts.all }}</div>
          <div class="stat-label">协作客户总数</div>
        </div>
      </a-col>
      <a-col :span="8">
        <div class="stat-card success">
          <div class="stat-num">{{ counts.editable }}</div>
          <div class="stat-label">可编辑</div>
        </div>
      </a-col>
      <a-col :span="8">
        <div class="stat-card muted">
          <div class="stat-num">{{ counts.readonly }}</div>
          <div class="stat-label">只读</div>
        </div>
      </a-col>
    </a-row>

    <!-- 操作栏 -->
    <div class="action-bar">
      <div class="action-left">
        <a-input-search
          v-model:value="queryParam.keyword"
          placeholder="搜索客户名称 / 简称 / 电话"
          style="width: 260px"
          allow-clear
          @search="loadData(true)"
        />
        <span class="hint">同事共享给你的客户，可查看详情并跟进</span>
      </div>
      <div class="action-right">
        <a-button @click="resetSearch">重置</a-button>
      </div>
    </div>

    <a-table
      class="collab-table"
      :columns="columns"
      :data-source="dataSource"
      :loading="loading"
      :pagination="pagination"
      :row-key="r => r.id"
      size="middle"
      :scroll="{ x: 860 }"
      @change="onTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'customer_name'">
          <div class="customer-cell">
            <a class="customer-link" @click="handleDetail(record)">{{ record.customer_name }}</a>
            <span class="customer-code">{{ record.customer_code }}</span>
          </div>
        </template>
        <template v-if="column.key === 'collab_permission'">
          <a-tag :color="Number(record.collab_permission) === 2 ? 'blue' : 'default'">
            {{ Number(record.collab_permission) === 2 ? '可编辑' : '只读' }}
          </a-tag>
        </template>
        <template v-if="column.key === 'last_followup_time'">
          {{ record.last_followup_time ? daysAgo(record.last_followup_time) : '从未跟进' }}
        </template>
      </template>
    </a-table>
  </a-card>
</template>

<script setup>
import { ref, reactive, onActivated } from 'vue'
import { useRouter } from 'vue-router'
import { getCollabList } from '@/api/crm/customer'

const router = useRouter()
const loading = ref(false)
const dataSource = ref([])
const counts = reactive({ all: 0, editable: 0, readonly: 0 })
const queryParam = reactive({})
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const columns = [
  { title: '客户', key: 'customer_name', width: 260 },
  { title: '行业', dataIndex: 'industry', key: 'industry', width: 120, ellipsis: true },
  { title: '负责人', dataIndex: 'owner_user_name', key: 'owner_user_name', width: 110 },
  { title: '我的权限', key: 'collab_permission', width: 100 },
  { title: '最近跟进', key: 'last_followup_time', width: 120 },
]

function daysAgo(t) {
  const d = Math.floor((Date.now() / 1000 - Number(t)) / 86400)
  if (d <= 0) return '今天'
  return d + ' 天前'
}

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { page: pagination.current, limit: pagination.pageSize, ...queryParam }
    const res = await getCollabList(params)
    if (res?.status === 200) {
      dataSource.value = res.data?.list?.data || []
      pagination.total = res.data?.list?.total || 0
      Object.assign(counts, res.data?.counts || {})
    }
  } finally { loading.value = false }
}

function onTableChange(pag) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  loadData()
}

function handleDetail(record) {
  router.push({ path: '/crm/customer/detail', query: { id: record.id, from: 'collab' } })
}

function resetSearch() {
  Object.keys(queryParam).forEach(k => delete queryParam[k])
  loadData(true)
}

loadData()
onActivated(() => loadData())
</script>

<style scoped>
.stats-row { margin-bottom: 16px; }
.stat-card {
  background: #fff; border-radius: 6px; padding: 16px 20px;
  transition: all .2s; border: 1px solid #f0f0f0;
}
.stat-card.success { border-left: 3px solid #52c41a; }
.stat-card.muted { border-left: 3px solid #bfbfbf; }
.stat-card .stat-num { font-size: 28px; font-weight: 700; color: #1a1a1a; line-height: 1.2; }
.stat-card .stat-label { font-size: 12px; color: #999; margin-top: 4px; }

.action-bar {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 16px; padding: 12px 16px;
  background: #fff; border-radius: 6px; border: 1px solid #f0f0f0;
}
.action-left { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
.action-right { display: flex; gap: 10px; align-items: center; flex-shrink: 0; }
.hint { font-size: 12px; color: #999; }

.customer-cell { display: flex; flex-direction: column; line-height: 1.4; }
.customer-link { font-weight: 500; }
.customer-code { font-size: 11px; color: #bbb; }

.collab-table { background: #fff; border-radius: 6px; }
:deep(.collab-table .ant-table-thead > tr > th) { background: #fafafa; font-weight: 600; font-size: 12px; color: #666; }
</style>
