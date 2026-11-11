<template>
  <a-card :bordered="false" class="pool-page">
    <!-- 总览 -->
    <a-row :gutter="16" class="stats-row">
      <a-col :span="8">
        <div class="stat-card">
          <div class="stat-num">{{ counts.all }}</div>
          <div class="stat-label">公海客户</div>
        </div>
      </a-col>
      <a-col :span="8">
        <div class="stat-card warn">
          <div class="stat-num">{{ counts.stale }}</div>
          <div class="stat-label">7 天以上未认领</div>
        </div>
      </a-col>
      <a-col :span="8">
        <div class="stat-card success">
          <div class="stat-num">{{ counts.today }}</div>
          <div class="stat-label">今日新进池</div>
        </div>
      </a-col>
    </a-row>

    <!-- 操作栏 -->
    <div class="action-bar">
      <div class="action-left">
        <a-input-search
          v-model:value="queryParam.keyword"
          placeholder="搜索客户名称 / 编码 / 电话"
          style="width: 260px"
          allow-clear
          @search="loadData(true)"
        />
        <span class="hint">未分配负责人的客户，认领后归到你名下</span>
      </div>
      <div class="action-right">
        <a-button @click="resetSearch">重置</a-button>
      </div>
    </div>

    <a-table
      class="pool-table"
      :columns="columns"
      :data-source="dataSource"
      :loading="loading"
      :pagination="pagination"
      :row-key="r => r.id"
      size="middle"
      :scroll="{ x: 820 }"
      @change="onTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'customer_name'">
          <div class="customer-cell">
            <a class="customer-link" @click="handleDetail(record)">{{ record.customer_name }}</a>
            <span class="customer-code">{{ record.customer_code }}</span>
          </div>
        </template>
        <template v-if="column.key === 'pool_days'">
          <a-tag v-if="!record.enter_pool_time" class="tag-none">时间未知</a-tag>
          <a-tag v-else :color="poolDays(record) >= 7 ? 'red' : 'blue'">已等待 {{ poolDays(record) }} 天</a-tag>
        </template>
        <template v-if="column.key === 'action'">
          <a-button type="primary" size="small" @click="handleClaim(record)">认领</a-button>
        </template>
      </template>
    </a-table>
  </a-card>
</template>

<script setup>
import { ref, reactive, onActivated } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { getPoolList, claim } from '@/api/crm/customer'

const router = useRouter()
const loading = ref(false)
const dataSource = ref([])
const counts = reactive({ all: 0, stale: 0, today: 0 })
const queryParam = reactive({})
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const columns = [
  { title: '客户', key: 'customer_name', width: 260 },
  { title: '行业', dataIndex: 'industry', key: 'industry', width: 130, ellipsis: true },
  { title: '来源', dataIndex: 'source', key: 'source', width: 110, ellipsis: true },
  { title: '入池时长', key: 'pool_days', width: 140 },
  { title: '操作', key: 'action', width: 100, fixed: 'right' },
]

function poolDays(record) {
  if (!record.enter_pool_time) return 0
  return Math.max(0, Math.floor((Date.now() / 1000 - Number(record.enter_pool_time)) / 86400))
}

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { page: pagination.current, limit: pagination.pageSize, ...queryParam }
    const res = await getPoolList(params)
    if (res?.status === 200) {
      const list = res.data?.list || {}
      dataSource.value = list?.data || []
      pagination.total = list?.total || 0
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
  router.push({ path: '/crm/customer/detail', query: { id: record.id, from: 'pool' } })
}

async function handleClaim(record) {
  try {
    const res = await claim(record.id)
    if (res?.status === 200) {
      message.success('认领成功')
      loadData()
    } else {
      message.error(res?.message || '认领失败')
    }
  } catch (e) {
    message.error('认领失败')
  }
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
.stat-card.warn { border-left: 3px solid #faad14; }
.stat-card.success { border-left: 3px solid #52c41a; }
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
.tag-none { background: #fafafa; color: #999; border-color: #e8e8e8; }

.pool-table { background: #fff; border-radius: 6px; }
:deep(.pool-table .ant-table-thead > tr > th) { background: #fafafa; font-weight: 600; font-size: 12px; color: #666; }
</style>
