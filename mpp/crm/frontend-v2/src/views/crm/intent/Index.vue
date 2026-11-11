<template>
  <a-card :bordered="false" class="intent-page">
    <div class="action-bar">
      <a-tabs v-model:activeKey="tab" size="small" class="view-tabs" @change="loadData(true)">
        <a-tab-pane key="all" tab="全部" />
        <a-tab-pane key="2" tab="待报价" />
        <a-tab-pane key="3" tab="已报价" />
        <a-tab-pane key="4" tab="已成交" />
        <a-tab-pane key="5" tab="搁置" />
      </a-tabs>
      <div class="action-right">
        <a-input v-model:value="keyword" placeholder="搜索需求号 / 标题" allow-clear style="width:200px" @pressEnter="loadData(true)" />
        <a-select v-model:value="assignee" placeholder="承接人" allow-clear show-search :options="userOptions" style="width:140px" @change="loadData(true)" />
        <a-button type="primary" @click="openCreate"><template #icon><plus-outlined /></template>新增需求</a-button>
      </div>
    </div>

    <a-table :columns="columns" :data-source="dataSource" :loading="loading" :pagination="pagination"
      row-key="id" size="middle" @change="onTableChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'intent_no'">
          <a @click="goDetail(record)">{{ record.intent_no }}</a>
        </template>
        <template v-else-if="column.key === 'catalog_name'">
          {{ record.catalog_name || '—' }}
        </template>
        <template v-else-if="column.key === 'quantity'">
          {{ record.quantity }} {{ record.unit }}
        </template>
        <template v-else-if="column.key === 'status'">
          <a-tag :color="statusColor(record.status)">{{ record.status_text }}</a-tag>
        </template>
        <template v-else-if="column.key === 'expected_date'">
          {{ record.expected_date ? new Date(record.expected_date * 1000).toLocaleDateString() : '—' }}
        </template>
        <template v-else-if="column.key === 'action'">
          <a @click="goDetail(record)">详情</a>
          <template v-if="!isClosed(record)">
            <a-divider type="vertical" />
            <a @click="openEdit(record)">编辑</a>
            <a-divider type="vertical" />
            <a @click="openAssign(record)">指派</a>
          </template>
          <template v-if="canQuote(record)">
            <a-divider type="vertical" />
            <a @click="goQuote(record)">报价</a>
          </template>
          <template v-if="record.status === 3">
            <a-divider type="vertical" />
            <a-popconfirm title="确认该需求已成交？" @confirm="handleConfirm(record)"><a>成交</a></a-popconfirm>
          </template>
          <template v-if="record.status === 5">
            <a-divider type="vertical" />
            <a @click="handleResume(record)">重启</a>
          </template>
          <template v-if="!isClosed(record)">
            <a-divider type="vertical" />
            <a @click="openReason(record, 'hold')">{{ record.status === 5 ? '改搁置' : '搁置' }}</a>
            <a-divider type="vertical" />
            <a style="color:#ff4d4f" @click="openReason(record, 'close')">关闭</a>
          </template>
        </template>
      </template>
    </a-table>

    <a-modal v-model:open="formVisible" :title="editingId ? '编辑需求' : '新增需求'"
      :confirm-loading="saving" width="680px" @ok="submitForm">
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="客户" required>
          <CustomerSelector v-model="form.customer_id" />
        </a-form-item>
        <a-form-item label="所属项目">
          <a-select v-model:value="form.project_id" allow-clear show-search :options="projectOptions" placeholder="可留空，未立项需求" />
        </a-form-item>
        <a-form-item label="需求标题" required>
          <a-input v-model:value="form.title" placeholder="例如：注册公司 / 代账 12 个月" />
        </a-form-item>
        <a-form-item label="服务品类">
          <ServiceCatalogSelect v-model="form.catalog_id" @change="onCatalogChange" />
        </a-form-item>
        <a-form-item label="数量">
          <a-input-number v-model:value="form.quantity" :min="0.01" :step="1" style="width:140px" />
          <a-input v-model:value="form.unit" placeholder="单位，如 单 / 月" style="width:120px;margin-left:8px" />
        </a-form-item>
        <a-form-item label="期望时间">
          <a-date-picker v-model:value="form.expected_date" style="width:180px" />
        </a-form-item>
        <a-form-item label="承接人">
          <a-select v-model:value="form.assignee_user_id" allow-clear show-search :options="userOptions" placeholder="默认由销售自己跟进" />
        </a-form-item>
        <a-form-item label="客户要求">
          <a-textarea v-model:value="form.description" :rows="3" placeholder="客户的具体要求、时间、预算等" />
        </a-form-item>
      </a-form>
    </a-modal>

    <a-modal v-model:open="assignVisible" title="指派承接人" :confirm-loading="saving" @ok="submitAssign">
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="需求">{{ current?.intent_no }} · {{ current?.title }}</a-form-item>
        <a-form-item label="承接人" required>
          <a-select v-model:value="assignUserId" show-search :options="userOptions" placeholder="选择同事" />
        </a-form-item>
      </a-form>
    </a-modal>

    <a-modal v-model:open="reasonVisible" :title="reasonMode === 'hold' ? '搁置需求' : '关闭需求'"
      :confirm-loading="saving" @ok="submitReason">
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item :label="reasonMode === 'hold' ? '搁置原因' : '关闭原因'" required>
          <a-textarea v-model:value="reason" :rows="3" placeholder="写清原因，方便后续接手" />
        </a-form-item>
        <a-form-item v-if="reasonMode === 'hold'" label="唤醒时间">
          <a-date-picker v-model:value="heldUntil" style="width:180px" />
        </a-form-item>
      </a-form>
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import dayjs from 'dayjs'
import { getList, getDetail, add, edit, assign, hold, resume, close, confirm } from '@/api/crm/intent'
import { getList as getProjects } from '@/api/crm/project'
import { list as listUsers } from '@/api/user'
import CustomerSelector from '@/components/app/CustomerSelector.vue'
import ServiceCatalogSelect from '@/components/app/ServiceCatalogSelect.vue'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const saving = ref(false)
const dataSource = ref([])
const tab = ref('all')
const keyword = ref('')
const assignee = ref(undefined)
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const storeUsers = ref([])
const userOptions = computed(() => storeUsers.value.map(u => ({
  value: u.store_user_id,
  label: u.real_name || u.user_name || ('#' + u.store_user_id),
})))

const projects = ref([])
const projectOptions = computed(() => projects.value.map(p => ({ value: p.id, label: p.project_no + ' · ' + p.title })))
function onCatalogChange(row) {
  if (row && row.default_assignee_user_id && !form.assignee_user_id) {
    form.assignee_user_id = row.default_assignee_user_id
  }
}

const columns = [
  { title: '需求号', dataIndex: 'intent_no', key: 'intent_no', width: 170 },
  { title: '客户', dataIndex: 'customer_name', key: 'customer_name', width: 170, ellipsis: true },
  { title: '标题', dataIndex: 'title', key: 'title', ellipsis: true },
  { title: '品类', dataIndex: 'catalog_name', key: 'catalog_name', width: 110 },
  { title: '数量', dataIndex: 'quantity', key: 'quantity', width: 90 },
  { title: '承接人', dataIndex: 'assignee_user_name', key: 'assignee_user_name', width: 90 },
  { title: '状态', dataIndex: 'status', key: 'status', width: 90 },
  { title: '期望时间', dataIndex: 'expected_date', key: 'expected_date', width: 110 },
  { title: '操作', key: 'action', width: 260 },
]

const formVisible = ref(false)
const editingId = ref(0)
const form = reactive({
  customer_id: undefined,
  project_id: undefined,
  title: '',
  catalog_id: undefined,
  quantity: 1,
  unit: '',
  expected_date: null,
  assignee_user_id: undefined,
  description: '',
})

const assignVisible = ref(false)
const assignUserId = ref(undefined)
const current = ref(null)

const reasonVisible = ref(false)
const reasonMode = ref('close')
const reason = ref('')
const heldUntil = ref(null)

function statusColor(s) {
  return s === 1 ? 'default' : s === 2 ? 'orange' : s === 3 ? 'blue' : s === 4 ? 'green' : s === 5 ? 'default' : 'red'
}
function isClosed(r) { return r.status === 6 }
function canQuote(r) { return r.status === 2 || r.status === 3 }

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { page: pagination.current, limit: pagination.pageSize }
    if (tab.value !== 'all') params.status = parseInt(tab.value)
    if (keyword.value) params.keyword = keyword.value
    if (assignee.value) params.assignee_user_id = assignee.value
    const res = await getList(params)
    if (res?.status === 200) {
      const list = res.data?.list || {}
      dataSource.value = list.data || []
      pagination.total = list.total || 0
    }
  } catch (e) {
    console.error('[需求池] 加载失败：', e)
  } finally { loading.value = false }
}

function onTableChange(pag) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  loadData()
}

function resetForm() {
  form.customer_id = undefined
  form.project_id = undefined
  form.title = ''
  form.catalog_id = undefined
  form.quantity = 1
  form.unit = ''
  form.expected_date = null
  form.assignee_user_id = undefined
  form.description = ''
}

async function openCreate(prefill = {}) {
  editingId.value = 0
  resetForm()
  Object.assign(form, prefill)
  if (form.project_id && !form.customer_id) {
    const p = projects.value.find(x => x.id === form.project_id)
    if (p) form.customer_id = p.customer_id
  }
  formVisible.value = true
}

async function openEdit(record) {
  try {
    const res = await getDetail(record.id)
    const i = res?.data?.intent || record
    editingId.value = i.id
    form.customer_id = i.customer_id
    form.project_id = i.project_id || undefined
    form.title = i.title
    form.catalog_id = i.catalog_id || undefined
    form.quantity = Number(i.quantity) || 1
    form.unit = i.unit || ''
    form.expected_date = i.expected_date ? dayjs(i.expected_date * 1000) : null
    form.assignee_user_id = i.assignee_user_id || undefined
    form.description = i.description || ''
    formVisible.value = true
  } catch (e) {
    console.error('[需求池] 读取详情失败：', e)
  }
}

async function submitForm() {
  if (!form.customer_id) { message.warning('请选择客户'); return }
  if (!form.title.trim()) { message.warning('请输入需求标题'); return }

  const payload = {
    customer_id: form.customer_id,
    project_id: form.project_id || 0,
    title: form.title.trim(),
    catalog_id: form.catalog_id || 0,
    quantity: form.quantity,
    unit: form.unit || '',
    expected_date: form.expected_date ? dayjs(form.expected_date).format('YYYY-MM-DD') : '',
    assignee_user_id: form.assignee_user_id || 0,
    description: form.description || '',
  }
  saving.value = true
  try {
    if (editingId.value) {
      await edit({ id: editingId.value, ...payload })
      message.success('保存成功')
    } else {
      await add({ ...payload, source: 2 })
      message.success('创建成功')
    }
    formVisible.value = false
    loadData()
  } catch (e) {
    console.error('[需求池] 保存失败：', e)
  } finally { saving.value = false }
}

function openAssign(record) {
  current.value = record
  assignUserId.value = record.assignee_user_id || undefined
  assignVisible.value = true
}

async function submitAssign() {
  if (!assignUserId.value) { message.warning('请选择承接人'); return }
  saving.value = true
  try {
    await assign(current.value.id, assignUserId.value)
    message.success('已指派')
    assignVisible.value = false
    loadData()
  } catch (e) {
    console.error('[需求池] 指派失败：', e)
  } finally { saving.value = false }
}

function openReason(record, mode) {
  current.value = record
  reasonMode.value = mode
  reason.value = ''
  heldUntil.value = null
  reasonVisible.value = true
}

async function submitReason() {
  if (!reason.value.trim()) { message.warning('请填写原因'); return }
  saving.value = true
  try {
    if (reasonMode.value === 'hold') {
      await hold(current.value.id, reason.value.trim(), heldUntil.value ? dayjs(heldUntil.value).format('YYYY-MM-DD') : null)
      message.success('已搁置')
    } else {
      await close(current.value.id, reason.value.trim())
      message.success('已关闭')
    }
    reasonVisible.value = false
    loadData()
  } catch (e) {
    console.error('[需求池] 操作失败：', e)
  } finally { saving.value = false }
}

async function handleResume(record) {
  try {
    await resume(record.id)
    message.success('已重新启动')
    loadData()
  } catch (e) { console.error('[需求池] 重启失败：', e) }
}

async function handleConfirm(record) {
  try {
    await confirm(record.id)
    message.success('已成交')
    loadData()
  } catch (e) { console.error('[需求池] 成交失败：', e) }
}

function goDetail(record) { router.push({ path: '/crm/intent/detail', query: { id: record.id } }) }
function goQuote(record) { router.push({ path: '/crm/quotation/index', query: { intent_id: record.id, customer_id: record.customer_id } }) }

async function loadUsers() {
  try {
    const res = await listUsers({})
    storeUsers.value = (res?.data?.list?.data || res?.data?.list || []).filter(u => u.is_supplier !== 1)
  } catch (e) { console.error('[需求池] 读取员工失败：', e) }
}

async function loadProjects() {
  try {
    const res = await getProjects({ limit: 200, archived: 0 })
    projects.value = res?.data?.list?.data || []
  } catch (e) { console.error('[需求池] 读取项目失败：', e) }
}



onMounted(async () => {
  await Promise.all([loadUsers(), loadProjects()])
  await loadData()
  if (route.query.create) {
    openCreate({
      project_id: route.query.project_id ? parseInt(route.query.project_id) : undefined,
    })
  }
})
</script>

<style scoped>
.intent-page :deep(.ant-card-body) { padding: 20px 24px; }
.action-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
.action-right { display: flex; gap: 10px; align-items: center; }
.view-tabs { min-width: 360px; }
.view-tabs :deep(.ant-tabs-nav) { margin: 0; }
.form-hint { color:#999; font-size:12px; margin-top:4px; }
</style>
