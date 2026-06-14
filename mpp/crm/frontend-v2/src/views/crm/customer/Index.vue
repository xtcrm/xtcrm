<template>
  <a-card :bordered="false" class="customer-page">
    <!-- 统计卡片 -->
    <a-row :gutter="16" class="stats-row">
      <a-col :span="6">
        <div class="stat-card" @click="followFilter=0; loadData(true)">
          <div class="stat-num">{{ pagination.total }}</div>
          <div class="stat-label">全部客户</div>
        </div>
      </a-col>
      <a-col :span="6">
        <div class="stat-card warn" @click="followFilter=7; loadData(true)">
          <div class="stat-num">{{ stats.pendingFollowup }}</div>
          <div class="stat-label">待跟进</div>
        </div>
      </a-col>
      <a-col :span="6">
        <div class="stat-card success">
          <div class="stat-num">{{ stats.newCustomers }}</div>
          <div class="stat-label">本月新增</div>
        </div>
      </a-col>
      <a-col :span="6">
        <div class="stat-card pool" @click="router.push('/crm/customer/pool')">
          <div class="stat-num">{{ stats.poolCount }}</div>
          <div class="stat-label">公海客户</div>
        </div>
      </a-col>
    </a-row>

    <!-- 操作栏 -->
    <div class="action-bar">
      <div class="action-left">
        <a-input-search
          v-model:value="queryParam.customer_name"
          placeholder="搜索客户名称..."
          style="width: 280px"
          allow-clear
          @search="loadData(true)"
        />
        <a-select
          v-model:value="queryParam.status"
          placeholder="客户状态"
          allow-clear
          style="width: 120px"
          @change="loadData(true)"
        >
          <a-select-option value="">全部状态</a-select-option>
          <a-select-option :value="1">正常</a-select-option>
          <a-select-option :value="0">停用</a-select-option>
        </a-select>
        <a-select
          v-model:value="queryParam.funnel_stage"
          placeholder="漏斗阶段"
          allow-clear
          style="width: 130px"
          @change="loadData(true)"
        >
          <a-select-option value="">全部阶段</a-select-option>
          <a-select-option :value="1">初步接触</a-select-option>
          <a-select-option :value="2">需求确认</a-select-option>
          <a-select-option :value="3">报价</a-select-option>
          <a-select-option :value="4">谈判</a-select-option>
          <a-select-option :value="5">成交</a-select-option>
        </a-select>
      </div>
      <div class="action-right">
        <a-input-search
          v-model:value="aiQuery"
          placeholder="AI智能搜索..."
          style="width: 240px"
          :loading="aiSearching"
          @search="handleAiSearch"
        >
          <template #enterButton>
            <a-button type="primary" :loading="aiSearching">🤖</a-button>
          </template>
        </a-input-search>
        <a-button type="primary" @click="handleAdd">
          <template #icon><plus-outlined /></template>
          新增客户
        </a-button>
      </div>
    </div>

    <!-- 表格 -->
    <a-table
      :columns="columns"
      :data-source="dataSource"
      :loading="loading"
      :pagination="pagination"
      :row-key="r => r.id"
      size="middle"
      class="customer-table"
      @change="onTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'customer_name'">
          <div class="customer-cell">
            <a-avatar :size="32" :style="{ backgroundColor: avatarColor(record.customer_name) }" style="flex-shrink:0">
              {{ record.customer_name?.charAt(0) }}
            </a-avatar>
            <div class="customer-info">
              <a class="customer-link" @click="handleDetail(record)">{{ record.customer_name }}</a>
              <div class="customer-code">{{ record.customer_code }}</div>
            </div>
          </div>
        </template>
        <template v-else-if="column.key === 'funnel_stage'">
          <span v-if="record.funnel_stage" class="funnel-tag" :class="'stage-' + record.funnel_stage">
            {{ ['', '初步接触', '需求确认', '报价', '谈判', '成交'][record.funnel_stage] }}
          </span>
        </template>
        <template v-else-if="column.key === 'health'">
          <div class="health-bar">
            <div class="health-fill" :style="{ width: healthPercent(record.last_followup_time) + '%', background: healthColor(record.last_followup_time) }"></div>
          </div>
          <span class="health-text" :style="{ color: healthColor(record.last_followup_time) }">
            {{ healthLabel(record.last_followup_time) }}
          </span>
        </template>
        <template v-else-if="column.key === 'status'">
          <a-badge :status="record.status === 1 ? 'success' : 'default'" :text="record.status === 1 ? '正常' : '停用'" />
        </template>
        <template v-else-if="column.key === 'action'">
          <a-space :size="0">
            <a-button type="link" size="small" @click="handleDetail(record)">详情</a-button>
            <a-button type="link" size="small" @click="handleEdit(record)">编辑</a-button>
            <a-popconfirm title="确认删除该客户？" @confirm="handleDelete(record)">
              <a-button type="link" size="small" danger>删除</a-button>
            </a-popconfirm>
          </a-space>
        </template>
      </template>
    </a-table>

    <CustomerForm v-model:open="formVisible" :mdl="selected" @ok="onFormOk" />
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import { getList, getDetail, deleteById, smartSearch } from '@/api/crm/customer'
import { getIndex } from '@/api/crm/dashboard'
import CustomerForm from './components/CustomerForm.vue'

const router = useRouter()

const loading = ref(false)
const dataSource = ref([])
const queryParam = reactive({})
const followFilter = ref(0)
const aiQuery = ref('')
const aiSearching = ref(false)
const formVisible = ref(false)
const selected = ref(null)
const stats = reactive({ pendingFollowup: 0, newCustomers: 0, poolCount: 0 })

function loadStats() {
  getIndex().then(res => {
    if (res?.status === 200) {
      const d = res.data || {}
      stats.pendingFollowup = d.pendingFollowup || 0
      stats.newCustomers = d.newCustomers || 0
      stats.poolCount = d.poolCount || 0
    }
  }).catch(() => {})
}

const pagination = reactive({
  current: 1, pageSize: 15, total: 0,
  showSizeChanger: true, showTotal: t => `共 ${t} 条`,
})

const columns = [
  { title: '客户名称', key: 'customer_name', width: 260, sorter: true },
  { title: '行业', dataIndex: 'industry', key: 'industry', width: 100 },
  { title: '等级', dataIndex: 'level_name', key: 'level_name', width: 80, align: 'center' },
  { title: '来源', dataIndex: 'source', key: 'source', width: 90 },
  { title: '分组', dataIndex: 'customer_group', key: 'customer_group', width: 90 },
  { title: '漏斗阶段', key: 'funnel_stage', width: 100, align: 'center' },
  { title: '负责人', dataIndex: 'owner_user_name', key: 'owner_user_name', width: 90 },
  { title: '跟进健康', key: 'health', width: 130 },
  { title: '状态', dataIndex: 'status', key: 'status', width: 70, align: 'center' },
  { title: '操作', key: 'action', width: 170, fixed: 'right' },
]

// 头像颜色
function avatarColor(name) {
  const colors = ['#1890ff', '#52c41a', '#fa8c16', '#722ed1', '#eb2f96', '#13c2c2', '#f5222d', '#2f54eb']
  let hash = 0
  for (let i = 0; i < (name || '').length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
  return colors[Math.abs(hash) % colors.length]
}

// 跟进健康度
function healthPercent(time) {
  if (!time) return 0
  const days = Math.floor((Date.now() / 1000 - time) / 86400)
  return Math.max(0, 100 - days * 2)
}

function healthColor(time) {
  if (!time) return '#d9d9d9'
  const days = Math.floor((Date.now() / 1000 - time) / 86400)
  if (days < 7) return '#52c41a'
  if (days < 30) return '#faad14'
  return '#ff4d4f'
}

function healthLabel(time) {
  if (!time) return '新客户'
  const days = Math.floor((Date.now() / 1000 - time) / 86400)
  if (days < 7) return '健康'
  if (days < 30) return days + '天'
  return days + '天'
}

// 加载数据
async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { page: pagination.current, limit: pagination.pageSize, ...queryParam }
    if (followFilter.value > 0) params.follow_days = followFilter.value
    const res = await getList(params)
    if (res?.status === 200) {
      const list = res.data?.list || {}
      const arr = list?.data || list || []
      dataSource.value = Array.isArray(arr) ? arr : []
      pagination.total = list?.total || 0
    }
  } finally { loading.value = false }
}

function onTableChange(pag) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  loadData()
}

function handleAdd() { selected.value = null; formVisible.value = true }

function handleEdit(record) {
  getDetail(record.id).then(res => {
    if (res?.status === 200) { selected.value = res.data?.detail || res.data; formVisible.value = true }
  })
}

function handleDetail(record) {
  router.push({ path: '/crm/customer/detail', query: { id: record.id, from: 'index' } })
}

async function handleDelete(record) {
  const res = await deleteById(record.id)
  if (res?.status === 200) { message.success('删除成功'); loadData() }
}

function onFormOk() { formVisible.value = false; loadData() }

function handleAiSearch(q) {
  if (!q) return
  aiSearching.value = true
  smartSearch(q).then(res => {
    if (res?.status === 200) {
      const filters = res.data?.filters || {}
      if (filters.customer_name) queryParam.customer_name = filters.customer_name
      if (filters.industry) queryParam.industry = filters.industry
      if (filters.level) queryParam.level_name = filters.level
      if (filters.source) queryParam.source = filters.source
      if (filters.group) queryParam.customer_group = filters.group
      if (filters.funnel_stage) {
        const m = { '初步接触': 1, '需求确认': 2, '报价': 3, '谈判': 4, '成交': 5 }
        queryParam.funnel_stage = m[filters.funnel_stage] || ''
      }
      if (filters.follow_days) followFilter.value = parseInt(filters.follow_days)
      message.success('已解析：' + Object.values(filters).filter(v => v).join(', '))
    }
    aiSearching.value = false
    loadData(true)
  }).catch(() => { aiSearching.value = false })
}

onMounted(() => { loadData(); loadStats() })
</script>

<style scoped>
/* 统计卡片 */
.stats-row { margin-bottom: 16px; }
.stat-card {
  background: #fff; border-radius: 6px; padding: 16px 20px;
  cursor: pointer; transition: all .2s; border: 1px solid #f0f0f0;
}
.stat-card:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.stat-card.warn { border-left: 3px solid #faad14; }
.stat-card.success { border-left: 3px solid #52c41a; }
.stat-card.pool { border-left: 3px solid #999; }
.stat-card .stat-num { font-size: 28px; font-weight: 700; color: #1a1a1a; line-height: 1.2; }
.stat-card .stat-label { font-size: 12px; color: #999; margin-top: 4px; }

/* 操作栏 */
.action-bar {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 16px; padding: 12px 16px;
  background: #fff; border-radius: 6px; border: 1px solid #f0f0f0;
}
.action-left { display: flex; gap: 10px; align-items: center; }
.action-right { display: flex; gap: 10px; align-items: center; }

/* 客户单元格 */
.customer-cell { display: flex; align-items: center; gap: 10px; }
.customer-link { font-weight: 500; font-size: 14px; }
.customer-code { font-size: 11px; color: #bbb; }

/* 漏斗标签 */
.funnel-tag {
  display: inline-block; padding: 1px 10px; border-radius: 10px;
  font-size: 12px; font-weight: 500;
}
.stage-1 { background: #f0f0f0; color: #666; }
.stage-2 { background: #fff7e6; color: #d46b08; }
.stage-3 { background: #e6f7ff; color: #1890ff; }
.stage-4 { background: #f9f0ff; color: #722ed1; }
.stage-5 { background: #f6ffed; color: #52c41a; }

/* 健康度 */
.health-bar { width: 60px; height: 4px; background: #f0f0f0; border-radius: 2px; overflow: hidden; margin-bottom: 2px; }
.health-fill { height: 100%; border-radius: 2px; transition: width .3s; }
.health-text { font-size: 11px; }

/* 表格 */
.customer-table { background: #fff; border-radius: 6px; }
:deep(.customer-table .ant-table) { border-radius: 6px; }
:deep(.customer-table .ant-table-thead > tr > th) { background: #fafafa; font-weight: 600; font-size: 12px; color: #666; }
</style>
