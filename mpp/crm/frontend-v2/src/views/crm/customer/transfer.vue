<template>
  <a-card :bordered="false" class="transfer-page">
    <a-alert
      type="info"
      show-icon
      class="tip"
      message="批量转让只变更客户负责人；项目、报价、订单、合同、跟进记录都不跟随转移。"
    />

    <!-- 模式切换 -->
    <a-radio-group v-model:value="mode" class="mode-tabs" @change="onModeChange">
      <a-radio-button value="owner">按人员整体转让</a-radio-button>
      <a-radio-button value="select">勾选转让</a-radio-button>
    </a-radio-group>

    <!-- 整体转让 -->
    <div v-if="mode === 'owner'" class="owner-panel">
      <div class="owner-form">
        <div class="field">
          <div class="field-label">原负责人</div>
          <a-select
            v-model:value="ownerForm.from_user_id"
            placeholder="选择原负责人"
            show-search
            allow-clear
            :filter-option="filterUserOption"
            style="width: 180px"
            @change="onFromChange"
          >
            <a-select-option v-for="u in storeUsers" :key="u.store_user_id" :value="u.store_user_id">
              {{ u.real_name || u.user_name }}
            </a-select-option>
          </a-select>
        </div>
        <arrow-right-outlined class="arrow" />
        <div class="field">
          <div class="field-label">新负责人</div>
          <a-select
            v-model:value="ownerForm.to_user_id"
            placeholder="选择新负责人"
            show-search
            allow-clear
            :filter-option="filterUserOption"
            :disabled="!ownerForm.from_user_id"
            style="width: 180px"
          >
            <a-select-option
              v-for="u in storeUsers"
              :key="u.store_user_id"
              :value="u.store_user_id"
              :disabled="u.store_user_id === ownerForm.from_user_id"
            >
              {{ u.real_name || u.user_name }}
            </a-select-option>
          </a-select>
        </div>
        <div class="field">
          <div class="field-label">转让备注</div>
          <a-input
            v-model:value="ownerForm.remark"
            placeholder="例如：张三离职，客户交接给李四"
            :maxlength="200"
            allow-clear
            style="width: 260px"
          />
        </div>
        <a-button type="primary" :disabled="!canOwnerTransfer" @click="openOwnerConfirm">
          整体转让<template v-if="ownerForm.from_user_id && ownerCount !== null">（{{ ownerCount }} 个客户）</template>
        </a-button>
      </div>
      <div class="owner-hint">
        <template v-if="ownerForm.from_user_id">
          「{{ userName(ownerForm.from_user_id) }}」名下共
          <strong>{{ ownerCount === null ? '…' : ownerCount }}</strong> 个客户<template v-if="ownerForm.to_user_id">，将全部转给「{{ userName(ownerForm.to_user_id) }}」</template>。
        </template>
        <template v-else>选择原负责人后，一键把其名下全部客户转给新负责人，无需逐个勾选。</template>
      </div>
    </div>

    <!-- 筛选（勾选模式） -->
    <div v-if="mode === 'select'" class="action-bar">
      <a-input-search
        v-model:value="queryParam.keyword"
        placeholder="搜索客户名称 / 编码 / 电话"
        style="width: 260px"
        allow-clear
        @search="loadData(true)"
      />
      <a-select
        v-model:value="queryParam.owner_user_id"
        placeholder="原负责人"
        allow-clear
        show-search
        :filter-option="filterUserOption"
        style="width: 160px"
        @change="loadData(true)"
      >
        <a-select-option v-for="u in storeUsers" :key="u.store_user_id" :value="u.store_user_id">
          {{ u.real_name || u.user_name }}
        </a-select-option>
      </a-select>
      <a-select
        v-model:value="queryParam.level_id"
        placeholder="客户等级"
        allow-clear
        style="width: 130px"
        @change="loadData(true)"
      >
        <a-select-option v-for="op in dictOptions.customer_level" :key="op.value" :value="op.value">
          {{ op.name }}
        </a-select-option>
      </a-select>
      <a-button @click="resetSearch">重置</a-button>
    </div>

    <!-- 勾选模式：批量操作栏 -->
    <div v-if="mode === 'select' && selectedRowKeys.length" class="batch-bar">
      <span class="batch-info">已选 <strong>{{ selectedRowKeys.length }}</strong> 条</span>
      <a-button
        v-if="pagination.total > selectedRowKeys.length"
        type="link"
        size="small"
        :loading="selectingAll"
        @click="selectAllMatching"
      >
        全选筛选结果（{{ pagination.total }} 条）
      </a-button>
      <a-button size="small" type="primary" @click="openTransfer">转让给…</a-button>
      <a-button size="small" @click="clearSelection">取消</a-button>
    </div>

    <a-empty
      v-if="mode === 'owner' && !ownerForm.from_user_id"
      description="选择原负责人后，这里会显示他名下的全部客户"
      class="owner-empty"
    />

    <a-table
      v-else
      class="transfer-table"
      :columns="columns"
      :data-source="dataSource"
      :loading="loading"
      :pagination="pagination"
      :row-key="r => r.id"
      :row-selection="mode === 'select' ? { selectedRowKeys, onChange: onSelectionChange, preserveSelectedRowKeys: true } : undefined"
      size="middle"
      :scroll="{ x: 1000 }"
      @change="onTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'customer_name'">
          <div class="customer-cell">
            <a class="customer-link" @click="handleDetail(record)">{{ record.customer_name }}</a>
            <span class="customer-code">{{ record.customer_code }}</span>
          </div>
        </template>
        <template v-else-if="column.key === 'owner'">
          <a-tag v-if="record.owner_user_name" color="blue">{{ record.owner_user_name }}</a-tag>
          <span v-else class="muted">公海</span>
        </template>
        <template v-else-if="column.key === 'status'">
          <a-badge :status="record.status === 1 ? 'success' : 'default'" :text="record.status === 1 ? '正常' : '停用'" />
        </template>
        <template v-else-if="column.key === 'last_followup'">
          <span class="muted">{{ formatDate(record.last_followup_time) }}</span>
        </template>
        <template v-else-if="column.key === 'action'">
          <a-button type="link" size="small" @click="handleDetail(record)">详情</a-button>
        </template>
      </template>
    </a-table>

    <!-- 勾选转让弹窗 -->
    <a-modal
      v-model:open="transferOpen"
      title="批量转让客户"
      ok-text="确认转让"
      :confirm-loading="saving"
      :mask-closable="false"
      @ok="submitTransfer"
    >
      <a-form layout="vertical">
        <a-form-item label="已选客户">
          <strong class="count-strong">{{ selectedRowKeys.length }}</strong> 个
        </a-form-item>
        <a-form-item label="新负责人" required>
          <a-select
            v-model:value="selectForm.to_user_id"
            placeholder="选择接收客户的同事"
            show-search
            :filter-option="filterUserOption"
            style="width: 100%"
          >
            <a-select-option v-for="u in storeUsers" :key="u.store_user_id" :value="u.store_user_id">
              {{ u.real_name || u.user_name }}
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="转让备注">
          <a-textarea
            v-model:value="selectForm.remark"
            :rows="3"
            :maxlength="200"
            show-count
            placeholder="例如：张三离职，客户交接给李四"
          />
        </a-form-item>
        <a-alert
          type="warning"
          show-icon
          message="转让后客户归属立即变更，原负责人将看不到这些客户；项目 / 报价 / 订单 / 合同不跟着转。"
        />
      </a-form>
    </a-modal>

    <!-- 整体转让确认弹窗 -->
    <a-modal
      v-model:open="ownerConfirmOpen"
      title="确认整体转让"
      ok-text="确认转让"
      :confirm-loading="saving"
      :mask-closable="false"
      @ok="submitOwnerTransfer"
    >
      <p class="confirm-line">
        将把「<strong>{{ userName(ownerForm.from_user_id) }}</strong>」名下
        <strong class="count-strong">{{ ownerCount }}</strong> 个客户，
        全部转给「<strong>{{ userName(ownerForm.to_user_id) }}</strong>」。
      </p>
      <p class="muted">只变更客户负责人，项目 / 报价 / 订单 / 合同 / 跟进记录不跟随转移。</p>
      <p v-if="ownerForm.remark" class="muted">备注：{{ ownerForm.remark }}</p>
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onActivated } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { ArrowRightOutlined } from '@ant-design/icons-vue'
import { getList, batchTransfer, transferByOwner } from '@/api/crm/customer'
import { getAll as getConfigs } from '@/api/crm/config'
import { list as getStoreUsers } from '@/api/user'

const router = useRouter()

const mode = ref('owner')
const loading = ref(false)
const selectingAll = ref(false)
const saving = ref(false)
const transferOpen = ref(false)
const ownerConfirmOpen = ref(false)
const dataSource = ref([])
const selectedRowKeys = ref([])
const storeUsers = ref([])
const queryParam = reactive({})
const dictOptions = reactive({ customer_level: [] })
const selectForm = reactive({ to_user_id: undefined, remark: '' })
const ownerForm = reactive({ from_user_id: undefined, to_user_id: undefined, remark: '' })
const ownerTotal = ref(null)

const pagination = reactive({
  current: 1, pageSize: 20, total: 0,
  showSizeChanger: true, showTotal: t => `共 ${t} 条`,
})

const columns = [
  { title: '客户名称', key: 'customer_name', width: 260 },
  { title: '行业', dataIndex: 'industry', key: 'industry', width: 120 },
  { title: '等级', dataIndex: 'level_name', key: 'level_name', width: 90, align: 'center' },
  { title: '分组', dataIndex: 'customer_group', key: 'customer_group', width: 110 },
  { title: '原负责人', key: 'owner', width: 110 },
  { title: '最近跟进', key: 'last_followup', width: 120 },
  { title: '状态', key: 'status', width: 90, align: 'center' },
  { title: '操作', key: 'action', width: 90, fixed: 'right' },
]

const ownerCount = computed(() => (ownerForm.from_user_id ? ownerTotal.value : null))
const canOwnerTransfer = computed(() =>
  !!ownerForm.from_user_id && !!ownerForm.to_user_id &&
  ownerForm.from_user_id !== ownerForm.to_user_id &&
  ownerTotal.value !== null && ownerTotal.value > 0 && !saving.value
)

function loadDicts() {
  getConfigs().then(res => {
    const data = res?.data || {}
    dictOptions.customer_level = (data.customer_level || [])
      .filter(i => i.config_value)
      .map(i => ({ value: parseInt(i.config_value), name: i.config_name }))
  }).catch(e => console.error('loadDicts failed:', e))
}

function loadUsers() {
  getStoreUsers({}).then(res => {
    const list = res?.data?.list?.data || res?.data?.list || []
    storeUsers.value = list.filter(u => u.is_supplier !== 1 && u.is_delete !== 1)
  }).catch(e => console.error('loadUsers failed:', e))
}

function userName(id) {
  const u = storeUsers.value.find(x => x.store_user_id === id)
  return u ? (u.real_name || u.user_name) : ''
}

function filterUserOption(input, option) {
  return (option.label || option.value || '').toLowerCase().includes(input.toLowerCase())
}

function formatDate(ts) {
  if (!ts) return '-'
  const d = new Date(ts * 1000)
  const p = n => (n < 10 ? '0' + n : n)
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`
}

function baseParams() {
  const params = { ...queryParam }
  Object.keys(params).forEach(k => {
    if (params[k] === '' || params[k] === null || params[k] === undefined) delete params[k]
  })
  return params
}

async function loadData(reset = false) {
  if (reset) {
    pagination.current = 1
    clearSelection()
  }
  loading.value = true
  try {
    const res = await getList({ ...baseParams(), page: pagination.current, limit: pagination.pageSize })
    if (res?.status === 200) {
      const list = res.data?.list || {}
      const arr = list?.data || list || []
      dataSource.value = Array.isArray(arr) ? arr : []
      pagination.total = list?.total || 0
      // 整体转让模式：记录该原负责人名下客户数（翻页时也保持同一个数）
      if (mode.value === 'owner' && ownerForm.from_user_id && queryParam.owner_user_id === ownerForm.from_user_id) {
        ownerTotal.value = pagination.total
      }
    }
  } finally {
    loading.value = false
  }
}

function onTableChange(pag) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  loadData()
}

function onSelectionChange(keys) {
  selectedRowKeys.value = keys
}

function clearSelection() {
  selectedRowKeys.value = []
}

function resetSearch() {
  Object.keys(queryParam).forEach(k => delete queryParam[k])
  if (mode.value === 'owner' && ownerForm.from_user_id) {
    queryParam.owner_user_id = ownerForm.from_user_id
  }
  loadData(true)
}

function onModeChange() {
  clearSelection()
  if (mode.value === 'owner') {
    if (ownerForm.from_user_id) queryParam.owner_user_id = ownerForm.from_user_id
    else delete queryParam.owner_user_id
  } else {
    delete queryParam.owner_user_id
  }
  loadData(true)
}

/** 选原负责人 → 表格跟着筛成他的客户，顺便拿到总数 */
function onFromChange(val) {
  ownerTotal.value = null
  if (ownerForm.to_user_id === val) ownerForm.to_user_id = undefined
  if (val) queryParam.owner_user_id = val
  else delete queryParam.owner_user_id
  loadData(true)
}

/** 勾选模式：按当前筛选条件选中全部客户（分页拉取，单次上限 2000） */
async function selectAllMatching() {
  if (pagination.total > 2000) {
    message.warning('单次最多转让 2000 个客户，将选中前 2000 条，请分批操作')
  }
  selectingAll.value = true
  try {
    const limit = 200
    let page = 1
    let all = []
    let total = pagination.total
    while (all.length < total && all.length < 2000 && page <= 50) {
      const res = await getList({ ...baseParams(), page, limit })
      if (res?.status !== 200) break
      const list = res.data?.list || {}
      const arr = list?.data || []
      if (!arr.length) break
      all = all.concat(arr.map(r => r.id))
      total = list?.total || total
      page++
    }
    selectedRowKeys.value = all.slice(0, 2000)
    message.success(`已选中 ${selectedRowKeys.value.length} 个客户`)
  } catch (e) {
    console.error('selectAllMatching failed:', e)
    message.error('加载客户失败，请重试')
  } finally {
    selectingAll.value = false
  }
}

function openTransfer() {
  if (!selectedRowKeys.value.length) return message.warning('请先选择客户')
  selectForm.to_user_id = undefined
  selectForm.remark = ''
  transferOpen.value = true
}

async function submitTransfer() {
  if (!selectForm.to_user_id) return message.warning('请选择新负责人')
  if (!selectedRowKeys.value.length) return message.warning('请先选择客户')
  saving.value = true
  try {
    const res = await batchTransfer({
      ids: selectedRowKeys.value.join(','),
      to_user_id: selectForm.to_user_id,
      remark: selectForm.remark,
    })
    if (res?.status === 200) {
      message.success(res.message || '转让成功')
      transferOpen.value = false
      clearSelection()
      loadData()
    }
  } catch (e) {
    console.error('batchTransfer failed:', e)
  } finally {
    saving.value = false
  }
}

function openOwnerConfirm() {
  if (!ownerForm.from_user_id) return message.warning('请选择原负责人')
  if (!ownerForm.to_user_id) return message.warning('请选择新负责人')
  if (ownerForm.from_user_id === ownerForm.to_user_id) return message.warning('原负责人和新负责人不能是同一个人')
  ownerConfirmOpen.value = true
}

async function submitOwnerTransfer() {
  saving.value = true
  try {
    const res = await transferByOwner({
      from_user_id: ownerForm.from_user_id,
      to_user_id: ownerForm.to_user_id,
      remark: ownerForm.remark,
    })
    if (res?.status === 200) {
      message.success(res.message || '转让成功')
      ownerConfirmOpen.value = false
      ownerForm.from_user_id = undefined
      ownerForm.to_user_id = undefined
      ownerForm.remark = ''
      ownerTotal.value = null
      delete queryParam.owner_user_id
      clearSelection()
      if (mode.value === 'select') loadData(true)
    }
  } catch (e) {
    console.error('transferByOwner failed:', e)
  } finally {
    saving.value = false
  }
}

function handleDetail(record) {
  router.push({ path: '/crm/customer/detail', query: { id: record.id, from: 'transfer' } })
}

onMounted(() => {
  loadDicts()
  loadUsers()
  if (mode.value === 'select' || ownerForm.from_user_id) loadData()
})

// keep-alive 下再次进入：刷新一次，避免看到已转让出去的旧数据。
// 首次激活紧跟 onMounted，跳过以免重复请求。
let firstActivated = true
onActivated(() => {
  if (firstActivated) { firstActivated = false; return }
  if (transferOpen.value || ownerConfirmOpen.value) return
  if (mode.value === 'select' || ownerForm.from_user_id) loadData()
})
</script>

<style scoped>
.tip { margin-bottom: 12px; }

.mode-tabs { display: block; margin-bottom: 14px; }
.mode-tabs :deep(.ant-radio-button-wrapper) { font-size: 14px; padding: 0 18px; height: 34px; line-height: 32px; }

.owner-panel {
  margin-bottom: 16px; padding: 14px 16px;
  background: #f6ffed; border: 1px solid #b7eb8f; border-radius: 6px;
}
.owner-form { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
.field { display: flex; flex-direction: column; gap: 4px; }
.field-label { font-size: 12px; color: #666; }
.arrow { color: #999; margin-bottom: 10px; }
.owner-hint { margin-top: 10px; font-size: 12px; color: #666; }
.owner-hint strong { color: #cf1322; }

.action-bar {
  display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
  margin-bottom: 16px; padding: 12px 16px;
  background: #fff; border-radius: 6px; border: 1px solid #f0f0f0;
}

.batch-bar {
  display: flex; gap: 10px; align-items: center;
  margin-bottom: 12px; padding: 8px 16px;
  background: #e6f7ff; border-radius: 6px; border: 1px solid #91d5ff;
}
.batch-info { font-size: 13px; color: #333; }

.customer-cell { display: flex; flex-direction: column; }
.customer-link { font-weight: 500; font-size: 14px; }
.customer-code { font-size: 11px; color: #bbb; }

.muted { color: #999; }
.confirm-line { font-size: 14px; }

.transfer-table { background: #fff; border-radius: 6px; }
.owner-empty { padding: 40px 0; background: #fff; border-radius: 6px; border: 1px solid #f0f0f0; }

.count-strong { font-size: 18px; color: #1677ff; }
</style>
