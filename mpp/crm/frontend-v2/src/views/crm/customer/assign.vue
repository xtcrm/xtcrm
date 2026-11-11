<template>
  <a-card :bordered="false" class="assign-page">
    <!-- 池子总览 -->
    <a-row :gutter="16" class="stats-row">
      <a-col :span="8">
        <div class="stat-card" :class="{ active: !queryParam.pool_type }" @click="switchPool('')">
          <div class="stat-num">{{ counts.all }}</div>
          <div class="stat-label">待分配总数</div>
        </div>
      </a-col>
      <a-col :span="8">
        <div class="stat-card warn" :class="{ active: queryParam.pool_type === 'new' }" @click="switchPool('new')">
          <div class="stat-num">{{ counts.new }}</div>
          <div class="stat-label">新单待办 · 计时中</div>
        </div>
      </a-col>
      <a-col :span="8">
        <div class="stat-card muted" :class="{ active: queryParam.pool_type === 'legacy' }" @click="switchPool('legacy')">
          <div class="stat-num">{{ counts.legacy }}</div>
          <div class="stat-label">历史遗留 · 须管理员指定</div>
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
        <span class="hint">存量客户只进这里，不开放组内认领</span>
      </div>
      <div class="action-right">
        <a-button @click="resetSearch">重置</a-button>
      </div>
    </div>

    <a-table
      class="assign-table"
      :columns="columns"
      :data-source="dataSource"
      :loading="loading"
      :pagination="pagination"
      :row-key="r => r.id"
      size="middle"
      :scroll="{ x: 960 }"
      @change="onTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'customer_name'">
          <div class="customer-cell">
            <a class="customer-link" @click="handleDetail(record)">{{ record.customer_name }}</a>
            <span class="customer-code">{{ record.customer_code }}</span>
          </div>
        </template>
        <template v-if="column.key === 'region'">
          {{ [record.province, record.city].filter(Boolean).join(' / ') || '-' }}
        </template>
        <template v-if="column.key === 'pool'">
          <a-tag v-if="record.is_legacy" class="tag-legacy">历史遗留</a-tag>
          <a-tag v-else :color="record.pool_days >= 3 ? 'red' : 'blue'">已等待 {{ record.pool_days }} 天</a-tag>
        </template>
        <template v-if="column.key === 'action'">
          <a-space>
            <a-button type="primary" size="small" @click="openAssign(record)">指定客服</a-button>
            <a-button v-if="!record.is_legacy" size="small" @click="handleClaim(record)">认领</a-button>
          </a-space>
        </template>
      </template>
    </a-table>

    <a-modal
      v-model:open="assignOpen"
      title="指定客服责任人"
      :confirm-loading="saving"
      :mask-closable="false"
      width="520px"
      @ok="submitAssign"
    >
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="客户">
          <a-input :value="current.customer_name" disabled />
        </a-form-item>
        <a-form-item label="客服责任人" required>
          <a-select
            v-model:value="assignForm.cs_user_id"
            show-search
            option-filter-prop="label"
            placeholder="选择客服人员"
          >
            <a-select-option
              v-for="u in storeUsers"
              :key="u.store_user_id"
              :value="u.store_user_id"
              :label="u.real_name || u.user_name"
            >
              {{ u.real_name || u.user_name }}
            </a-select-option>
          </a-select>
        </a-form-item>
      </a-form>
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive, onActivated } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { getAssignList, assignCs, claimCs } from '@/api/crm/customer'
import { list as getStoreUsers } from '@/api/user'

const router = useRouter()
const loading = ref(false)
const saving = ref(false)
const dataSource = ref([])
const storeUsers = ref([])
const counts = reactive({ all: 0, new: 0, legacy: 0 })
const queryParam = reactive({})
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const assignOpen = ref(false)
const current = ref({})
const assignForm = reactive({ cs_user_id: undefined })

const columns = [
  { title: '客户', key: 'customer_name', width: 260 },
  { title: '销售归属', dataIndex: 'owner_user_name', key: 'owner_user_name', width: 110 },
  { title: '行业', dataIndex: 'industry', key: 'industry', width: 120, ellipsis: true },
  { title: '地区', key: 'region', width: 150, ellipsis: true },
  { title: '待分配状态', key: 'pool', width: 140 },
  { title: '操作', key: 'action', width: 180, fixed: 'right' },
]

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { page: pagination.current, list_rows: pagination.pageSize, ...queryParam }
    const res = await getAssignList(params)
    if (res?.status === 200) {
      dataSource.value = res.data?.list || []
      pagination.total = res.data?.total || 0
      Object.assign(counts, res.data?.counts || {})
    }
  } finally { loading.value = false }
}

function switchPool(type) {
  if (type) queryParam.pool_type = type
  else delete queryParam.pool_type
  loadData(true)
}

function onTableChange(pag) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  loadData()
}

function resetSearch() {
  Object.keys(queryParam).forEach(k => delete queryParam[k])
  loadData(true)
}

function handleDetail(record) {
  router.push({ path: '/crm/customer/detail', query: { id: record.id, from: 'assign' } })
}

function ensureUsers() {
  if (storeUsers.value.length) return
  getStoreUsers({}).then(res => {
    const list = res?.data?.list?.data || res?.data?.list || []
    storeUsers.value = list.filter(u => u.is_supplier !== 1)
  }).catch(() => {})
}

function openAssign(record) {
  current.value = record
  assignForm.cs_user_id = undefined
  ensureUsers()
  assignOpen.value = true
}

async function submitAssign() {
  if (!assignForm.cs_user_id) return message.warning('请选择客服人员')
  saving.value = true
  try {
    const res = await assignCs({ id: current.value.id, cs_user_id: assignForm.cs_user_id })
    if (res?.status === 200) {
      message.success('已指定客服')
      assignOpen.value = false
      loadData()
    } else {
      message.error(res?.message || '操作失败')
    }
  } catch (e) {
    message.error('操作失败')
  } finally { saving.value = false }
}

async function handleClaim(record) {
  try {
    const res = await claimCs(record.id)
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

loadData()
onActivated(() => loadData())
</script>

<style scoped>
.stats-row { margin-bottom: 16px; }
.stat-card {
  background: #fff; border-radius: 6px; padding: 16px 20px;
  cursor: pointer; transition: all .2s; border: 1px solid #f0f0f0;
}
.stat-card:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.stat-card.active { border-color: #1890ff; box-shadow: 0 0 0 2px rgba(24,144,255,.12); }
.stat-card.warn { border-left: 3px solid #faad14; }
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
.tag-legacy { background: #fafafa; color: #999; border-color: #e8e8e8; }

.assign-table { background: #fff; border-radius: 6px; }
:deep(.assign-table .ant-table-thead > tr > th) { background: #fafafa; font-weight: 600; font-size: 12px; color: #666; }
</style>
