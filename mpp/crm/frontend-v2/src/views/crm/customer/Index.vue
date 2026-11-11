<template>
  <a-card :bordered="false" class="customer-page">
    <!-- 视图切换 -->
    <div class="view-tabs">
      <a-radio-group v-model:value="viewMode" size="large" button-style="solid" @change="loadData(true)">
        <a-radio-button value="all">全部客户</a-radio-button>
        <a-radio-button value="mine">我的客户</a-radio-button>
        <a-radio-button value="others">同事客户</a-radio-button>
      </a-radio-group>
    </div>

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
          v-model:value="queryParam.keyword"
          placeholder="搜索客户名称..."
          style="width: 240px"
          allow-clear
          @search="loadData(true)"
        />
        <a-select
          v-model:value="queryParam.owner_user_id"
          placeholder="所属人员"
          allow-clear
          show-search
          :filter-option="filterUserOption"
          style="width: 150px"
          @change="loadData(true)"
        >
          <a-select-option v-for="u in storeUsers" :key="u.store_user_id" :value="u.store_user_id">
            {{ u.real_name || u.user_name }}
          </a-select-option>
        </a-select>
        <a-select
          v-model:value="queryParam.funnel_stage"
          placeholder="漏斗阶段"
          allow-clear
          style="width: 120px"
          @change="loadData(true)"
        >
          <a-select-option value="">全部阶段</a-select-option>
          <a-select-option :value="1">初步接触</a-select-option>
          <a-select-option :value="2">需求确认</a-select-option>
          <a-select-option :value="3">报价</a-select-option>
          <a-select-option :value="4">谈判</a-select-option>
          <a-select-option :value="5">成交</a-select-option>
        </a-select>
        <a-select
          v-model:value="queryParam.status"
          placeholder="客户状态"
          allow-clear
          style="width: 100px"
          @change="loadData(true)"
        >
          <a-select-option value="">全部状态</a-select-option>
          <a-select-option :value="1">正常</a-select-option>
          <a-select-option :value="0">停用</a-select-option>
        </a-select>
        <a-button type="link" size="small" @click="showMore = !showMore">
          更多筛选 <DownOutlined :rotate="showMore ? 180 : 0" />
        </a-button>
      </div>
      <div class="action-right">
        <a-button type="primary" @click="handleAdd">
          <template #icon><plus-outlined /></template>
          新增客户
        </a-button>
        <a-button @click="importVisible = true">
          <template #icon><upload-outlined /></template>
          导入
        </a-button>
      </div>
      <ImportModal :open="importVisible" @update:open="importVisible = false" @ok="loadData(true)" />
    </div>

    <!-- 更多筛选（可折叠） -->
    <div v-if="showMore" class="filter-row">
      <a-select
        v-model:value="queryParam.industry_id"
        placeholder="行业"
        allow-clear
        style="width: 120px"
        @change="loadData(true)"
      >
        <a-select-option v-for="op in dictOptions.industry" :key="op.value" :value="op.value">{{ op.name }}</a-select-option>
      </a-select>
      <a-select
        v-model:value="queryParam.group_id"
        placeholder="客户分组"
        allow-clear
        style="width: 130px"
        @change="loadData(true)"
      >
        <a-select-option v-for="op in dictOptions.customer_group" :key="op.value" :value="op.value">{{ op.name }}</a-select-option>
      </a-select>
      <a-select
        v-model:value="queryParam.level_id"
        placeholder="客户等级"
        allow-clear
        style="width: 130px"
        @change="loadData(true)"
      >
        <a-select-option v-for="op in dictOptions.customer_level" :key="op.value" :value="op.value">{{ op.name }}</a-select-option>
      </a-select>
      <a-select
        v-model:value="queryParam.source_id"
        placeholder="客户来源"
        allow-clear
        style="width: 120px"
        @change="loadData(true)"
      >
        <a-select-option v-for="op in dictOptions.customer_source" :key="op.value" :value="op.value">{{ op.name }}</a-select-option>
      </a-select>
      <a-select
        v-model:value="queryParam.tag_ids"
        placeholder="标签"
        allow-clear
        mode="multiple"
        style="width: 200px"
        @change="loadData(true)"
      >
        <a-select-opt-group v-for="g in tagFilterGroups" :key="'g'+g.id" :label="g.name">
          <a-select-option v-for="t in g.tags" :key="'t'+t.id" :value="t.id">
            <a-tag :color="t.color" style="margin:0">{{ t.name }}</a-tag>
          </a-select-option>
        </a-select-opt-group>
      </a-select>
    </div>

    <!-- 批量操作栏 -->
    <div v-if="selectedRowKeys.length" class="batch-bar">
      <span class="batch-info">已选 <strong>{{ selectedRowKeys.length }}</strong> 条</span>
      <a-select v-model:value="batchField" placeholder="修改字段" style="width:120px" size="small">
        <a-select-option value="customer_group">客户分组</a-select-option>
        <a-select-option value="source">客户来源</a-select-option>
        <a-select-option value="level_name">客户等级</a-select-option>
        <a-select-option value="industry">客户行业</a-select-option>
      </a-select>
      <a-select v-model:value="batchValue" placeholder="选择新值" style="width:150px" size="small" :options="batchOptions" />
      <a-button size="small" type="primary" :loading="batchSaving" @click="handleBatchUpdate">应用</a-button>
      <a-popconfirm title="确定删除选中客户？" @confirm="handleBatchDelete">
        <a-button size="small" danger :loading="batchDeleting">批量删除</a-button>
      </a-popconfirm>
      <a-button size="small" @click="selectedRowKeys=[]">取消</a-button>
    </div>

    <!-- 表格 -->
    <a-table
      :columns="columns"
      :data-source="dataSource"
      :loading="loading"
      :pagination="pagination"
      :row-selection="{ selectedRowKeys, onChange: (keys) => selectedRowKeys = keys }"
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
import { ref, reactive, computed, onMounted, onActivated } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUserStore } from '@/stores/user'
const userStore = useUserStore()
import { message } from 'ant-design-vue'
import { PlusOutlined, UploadOutlined, DownOutlined } from '@ant-design/icons-vue'
import { getList, getDetail, deleteById, batchUpdate, batchDelete } from '@/api/crm/customer'
import { getIndex } from '@/api/crm/dashboard'
import { getAll as getConfigs } from '@/api/crm/config'
import { getAllByTarget } from '@/api/crm/tag'
import { list as getStoreUsers } from '@/api/user'
import CustomerForm from './components/CustomerForm.vue'
import ImportModal from './Import.vue'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const dataSource = ref([])
const queryParam = reactive({ tag_ids: [] })

// 首页漏斗带 ?funnel_stage=N 跳进来。页签被我归一成同一个「客户列表」，
// 缓存页不会再 mount，所以阶段筛选要在 activated 时同步。
function routeFunnelStage() {
  const n = parseInt(route.query.funnel_stage)
  return n >= 1 && n <= 5 ? n : 0
}
let appliedRouteStage = routeFunnelStage()
if (appliedRouteStage) queryParam.funnel_stage = appliedRouteStage
const viewMode = ref(userStore.isSuper ? 'all' : 'mine')
const followFilter = ref(0)
const formVisible = ref(false)
const importVisible = ref(false)
const selected = ref(null)
const selectedRowKeys = ref([])
const batchField = ref('customer_group')
const batchValue = ref('')
const tagFilterGroups = ref([])
const batchSaving = ref(false)
const batchDeleting = ref(false)
const stats = reactive({ pendingFollowup: 0, newCustomers: 0, poolCount: 0 })
const dictOptions = reactive({ industry: [], customer_level: [], customer_source: [], customer_group: [] })
const showMore = ref(false)
const storeUsers = ref([])

function loadDicts() {
  getConfigs().then(res => {
    const data = res?.data || {}
    const toOpts = (arr) => (arr || []).filter(i => i.config_value).map(i => ({ value: parseInt(i.config_value), name: i.config_name }))
    dictOptions.industry = toOpts(data.industry)
    dictOptions.customer_level = toOpts(data.customer_level)
    dictOptions.customer_source = toOpts(data.customer_source)
    dictOptions.customer_group = toOpts(data.customer_group)
  })
}

function loadUsers() {
  getStoreUsers({}).then(res => {
    const list = res?.data?.list?.data || res?.data?.list || []
    storeUsers.value = list.filter(u => u.is_supplier !== 1)
  }).catch(() => {})
}

function filterUserOption(input, option) {
  return (option.label || option.value || '').toLowerCase().includes(input.toLowerCase())
}

const DICT_MAP = { customer_group: 'customer_group', source: 'customer_source', level_name: 'customer_level', industry: 'industry' }
const batchOptions = computed(() => (dictOptions[DICT_MAP[batchField.value]] || []).map(o => ({ label: o.name, value: o.name })))

function loadTagFilterOptions() {
  getAllByTarget('customer').then(res => {
    tagFilterGroups.value = res?.data?.list || []
  }).catch(e => { console.error('loadTagFilterOptions failed:', e) })
}

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
  { title: '行业', dataIndex: 'industry', key: 'industry', width: 100, sorter: true },
  { title: '等级', dataIndex: 'level_name', key: 'level_name', width: 80, align: 'center', sorter: true },
  { title: '来源', dataIndex: 'source', key: 'source', width: 90, sorter: true },
  { title: '分组', dataIndex: 'customer_group', key: 'customer_group', width: 90, sorter: true },
  { title: '漏斗阶段', key: 'funnel_stage', width: 100, align: 'center', sorter: true },
  { title: '负责人', dataIndex: 'owner_user_name', key: 'owner_user_name', width: 90 },
  { title: '跟进健康', dataIndex: 'last_followup_time', key: 'health', width: 130, sorter: true },
  { title: '状态', dataIndex: 'status', key: 'status', width: 70, align: 'center', sorter: true },
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
    // 视图过滤
    if (viewMode.value === 'mine') {
      params.owner_user_id = userStore.userInfo?.store_user_id || 0
    } else if (viewMode.value === 'others') {
      params.exclude_owner = userStore.userInfo?.store_user_id || 0
    }
    const res = await getList(params)
    if (res?.status === 200) {
      const list = res.data?.list || {}
      const arr = list?.data || list || []
      dataSource.value = Array.isArray(arr) ? arr : []
      pagination.total = list?.total || 0
    }
  } finally { loading.value = false }
}

function onTableChange(pag, filters, sorter) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  queryParam.sort_field = sorter.field || sorter.columnKey
  queryParam.sort_order = sorter.order === 'ascend' ? 'asc' : sorter.order === 'descend' ? 'desc' : undefined
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

async function handleBatchUpdate() {
  if (!selectedRowKeys.value.length) return message.warning('请选择客户')
  if (!batchValue.value) return message.warning('请输入新值')
  batchSaving.value = true
  try {
    const res = await batchUpdate({ ids: selectedRowKeys.value, field: batchField.value, value: batchValue.value })
    if (res?.status === 200) { message.success(res.data?.msg || `已更新 ${res.data?.count || 0} 条`); selectedRowKeys.value = []; loadData() }
  } finally { batchSaving.value = false }
}

async function handleBatchDelete() {
  if (!selectedRowKeys.value.length) return
  batchDeleting.value = true
  try {
    const res = await batchDelete(selectedRowKeys.value)
    if (res?.status === 200) { message.success(`已删除 ${res.data?.count || 0} 条`); selectedRowKeys.value = []; loadData() }
  } finally { batchDeleting.value = false }
}

function onFormOk() { formVisible.value = false; loadData() }

function handleAiSearch(q) {
  if (!q) return
  aiSearching.value = true
  smartSearch(q).then(res => {
    if (res?.status === 200) {
      const filters = res.data?.filters || {}
      if (filters.customer_name) queryParam.keyword = filters.customer_name
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

onMounted(() => { loadDicts(); loadUsers(); loadData(); loadStats(); loadTagFilterOptions() })

// 再次激活时对齐首页漏斗带过来的阶段（点普通「客户列表」页签则回到全部阶段）
onActivated(() => {
  const stage = routeFunnelStage()
  if (stage === appliedRouteStage) return
  appliedRouteStage = stage
  if (stage) queryParam.funnel_stage = stage
  else delete queryParam.funnel_stage
  loadData(true)
})
</script>

<style scoped>
.view-tabs { margin-bottom: 12px; }
.view-tabs :deep(.ant-radio-button-wrapper) { font-size: 15px; padding: 0 20px; height: 38px; line-height: 36px; }

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
.action-left { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.action-right { display: flex; gap: 10px; align-items: center; flex-shrink: 0; }

/* 折叠筛选行 */
.filter-row {
  display: flex; gap: 10px; align-items: center;
  margin-bottom: 16px; padding: 10px 16px;
  background: #fafafa; border-radius: 6px; border: 1px solid #f0f0f0;
}

.batch-bar {
  display: flex; gap: 10px; align-items: center;
  margin-bottom: 12px; padding: 8px 16px;
  background: #e6f7ff; border-radius: 6px; border: 1px solid #91d5ff;
}
.batch-info { font-size: 13px; color: #333; margin-right: 8px; }

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
