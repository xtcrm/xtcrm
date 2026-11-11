<template>
  <a-card :bordered="false" class="project-page">
    <div class="action-bar">
      <a-tabs v-model:activeKey="view" size="small" class="view-tabs" @change="loadData(true)">
        <a-tab-pane key="all" :tab="`全部 ${stats.all || 0}`" />
        <a-tab-pane key="mine" :tab="`我负责 ${stats.mine || 0}`" />
        <a-tab-pane key="joined" :tab="`我参与 ${stats.joined || 0}`" />
        <a-tab-pane key="archived" :tab="`已归档 ${stats.archived || 0}`" />
      </a-tabs>
      <div class="action-right">
        <a-input v-model:value="keyword" placeholder="搜索项目号 / 名称" allow-clear style="width:200px" @pressEnter="loadData(true)" />
        <a-select v-model:value="status" placeholder="状态" allow-clear style="width:110px" @change="loadData(true)">
          <a-select-option :value="1">潜在</a-select-option>
          <a-select-option :value="2">进行中</a-select-option>
          <a-select-option :value="3">已完成</a-select-option>
          <a-select-option :value="4">已取消</a-select-option>
        </a-select>
        <a-button type="primary" @click="openCreate"><template #icon><plus-outlined /></template>新建项目</a-button>
      </div>
    </div>

    <a-table :columns="columns" :data-source="dataSource" :loading="loading" :pagination="pagination"
      row-key="id" size="middle" @change="onTableChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'project_no'">
          <a @click="goDetail(record)">{{ record.project_no }}</a>
        </template>
        <template v-else-if="column.key === 'title'">
          <a @click="goDetail(record)">{{ record.title }}</a>
        </template>
        <template v-else-if="column.key === 'status'">
          <a-tag :color="statusColor(record.status)">{{ record.status_text }}</a-tag>
        </template>
        <template v-else-if="column.key === 'amount_total'">
          {{ record.amount_total ? Number(record.amount_total).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) : '—' }}
        </template>
        <template v-else-if="column.key === 'update_time'">
          {{ record.update_time ? new Date(record.update_time * 1000).toLocaleDateString() : '' }}
        </template>
        <template v-else-if="column.key === 'action'">
          <a @click="goDetail(record)">详情</a>
          <template v-if="!record.archived_at">
            <a-divider type="vertical" />
            <a @click="openEdit(record)">编辑</a>
          </template>
          <template v-if="record.status !== 4 && !record.archived_at">
            <a-divider type="vertical" />
            <a-popconfirm title="确认取消该项目？未完成的需求与交付会一并关闭" @confirm="handleCancel(record)">
              <a style="color:#ff4d4f">取消</a>
            </a-popconfirm>
          </template>
          <template v-if="record.status === 3 || record.status === 4">
            <a-divider type="vertical" v-if="!record.archived_at" />
            <a v-if="!record.archived_at" @click="handleArchive(record)">归档</a>
          </template>
        </template>
      </template>
    </a-table>

    <a-modal v-model:open="formVisible" :title="editingId ? '编辑项目' : '新建项目'"
      :confirm-loading="saving" width="640px" @ok="handleSubmit">
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="客户" required>
          <CustomerSelector v-model="form.customer_id" :disabled="!!editingId" />
        </a-form-item>
        <a-form-item label="项目名称" required>
          <a-input v-model:value="form.title" placeholder="例如：德飞名车 · 注册 + 首年代账" />
        </a-form-item>
        <a-form-item label="项目负责人">
          <a-select v-model:value="form.owner_user_id" allow-clear show-search :options="userOptions" placeholder="默认取客户负责人" />
        </a-form-item>
        <a-form-item label="销售负责人">
          <a-select v-model:value="form.sales_user_id" allow-clear show-search :options="userOptions" placeholder="默认取客户负责人" />
        </a-form-item>
        <a-form-item label="服务期">
          <a-range-picker v-model:value="form.period" style="width:100%" />
          <div class="form-hint">周期服务（如代账）才需要填；一次性项目可留空</div>
        </a-form-item>
        <a-form-item label="备注">
          <a-textarea v-model:value="form.remark" :rows="3" placeholder="选填" />
        </a-form-item>
      </a-form>
    </a-modal>

    <a-modal v-model:open="cancelVisible" title="取消项目" :confirm-loading="saving" @ok="submitCancel">
      <a-alert type="warning" show-icon message="未完成的需求与交付会一并关闭，已完成的历史保留。" style="margin-bottom:12px" />
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="取消原因" required>
          <a-textarea v-model:value="cancelReason" :rows="3" placeholder="写清原因，方便后续接手" />
        </a-form-item>
      </a-form>
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import dayjs from 'dayjs'
import { getList, getDetail, add, edit, cancel, archive } from '@/api/crm/project'
import { list as listUsers } from '@/api/user'
import CustomerSelector from '@/components/app/CustomerSelector.vue'

const router = useRouter()

const loading = ref(false)
const saving = ref(false)
const dataSource = ref([])
const stats = reactive({ all: 0, mine: 0, joined: 0, archived: 0 })
const view = ref('all')
const keyword = ref('')
const status = ref(undefined)
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 个项目` })

const storeUsers = ref([])
const userOptions = computed(() => storeUsers.value.map(u => ({
  value: u.store_user_id,
  label: u.real_name || u.user_name || ('#' + u.store_user_id),
})))

const columns = [
  { title: '项目号', dataIndex: 'project_no', key: 'project_no', width: 170 },
  { title: '项目名称', dataIndex: 'title', key: 'title', ellipsis: true },
  { title: '客户', dataIndex: 'customer_name', key: 'customer_name', width: 180, ellipsis: true },
  { title: '负责人', dataIndex: 'owner_user_name', key: 'owner_user_name', width: 90 },
  { title: '状态', dataIndex: 'status', key: 'status', width: 90 },
  { title: '需求', dataIndex: 'intent_count', key: 'intent_count', width: 70, align: 'center' },
  { title: '订单金额', dataIndex: 'amount_total', key: 'amount_total', width: 110, align: 'right' },
  { title: '更新时间', dataIndex: 'update_time', key: 'update_time', width: 110 },
  { title: '操作', key: 'action', width: 180 },
]

const formVisible = ref(false)
const editingId = ref(0)
const cancelVisible = ref(false)
const cancelTarget = ref(null)
const cancelReason = ref('')
const form = reactive({
  customer_id: undefined,
  title: '',
  owner_user_id: undefined,
  sales_user_id: undefined,
  period: null,
  remark: '',
})

function statusColor(s) {
  return s === 1 ? 'default' : s === 2 ? 'blue' : s === 3 ? 'green' : 'red'
}

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = {
      page: pagination.current,
      limit: pagination.pageSize,
      view: view.value,
      archived: view.value === 'archived' ? 1 : 0,
    }
    if (keyword.value) params.keyword = keyword.value
    if (status.value !== undefined && status.value !== null) params.status = status.value
    const res = await getList(params)
    if (res?.status === 200) {
      const list = res.data?.list || {}
      dataSource.value = list.data || []
      pagination.total = list.total || 0
      Object.assign(stats, res.data?.stats || {})
    }
  } catch (e) {
    console.error('[项目列表] 加载失败：', e)
  } finally { loading.value = false }
}

function onTableChange(pag) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  loadData()
}

function resetForm() {
  form.customer_id = undefined
  form.title = ''
  form.owner_user_id = undefined
  form.sales_user_id = undefined
  form.period = null
  form.remark = ''
}

function openCreate() {
  editingId.value = 0
  resetForm()
  formVisible.value = true
}

async function openEdit(record) {
  editingId.value = record.id
  resetForm()
  try {
    const res = await getDetail(record.id)
    const p = res?.data?.project || record
    form.customer_id = p.customer_id
    form.title = p.title
    form.owner_user_id = p.owner_user_id || undefined
    form.sales_user_id = p.sales_user_id || undefined
    form.remark = p.remark || ''
    form.period = p.service_start_time && p.service_end_time
      ? [dayjs(p.service_start_time * 1000), dayjs(p.service_end_time * 1000)]
      : null
    formVisible.value = true
  } catch (e) {
    console.error('[项目] 读取详情失败：', e)
  }
}

async function handleSubmit() {
  if (!form.customer_id) { message.warning('请选择客户'); return }
  if (!form.title.trim()) { message.warning('请输入项目名称'); return }

  const payload = {
    customer_id: form.customer_id,
    title: form.title.trim(),
    owner_user_id: form.owner_user_id || 0,
    sales_user_id: form.sales_user_id || 0,
    remark: form.remark || '',
    service_start_time: form.period?.[0] ? dayjs(form.period[0]).format('YYYY-MM-DD') : '',
    service_end_time: form.period?.[1] ? dayjs(form.period[1]).format('YYYY-MM-DD') : '',
  }
  saving.value = true
  try {
    if (editingId.value) {
      await edit({ id: editingId.value, ...payload })
      message.success('保存成功')
    } else {
      await add(payload)
      message.success('创建成功')
    }
    formVisible.value = false
    loadData()
  } catch (e) {
    console.error('[项目] 保存失败：', e)
  } finally { saving.value = false }
}

function handleCancel(record) {
  cancelTarget.value = record
  cancelReason.value = ''
  cancelVisible.value = true
}

async function submitCancel() {
  if (!cancelReason.value.trim()) { message.warning('请填写取消原因'); return }
  saving.value = true
  try {
    await cancel(cancelTarget.value.id, cancelReason.value.trim())
    message.success('已取消')
    cancelVisible.value = false
    loadData()
  } catch (e) {
    console.error('[项目] 取消失败：', e)
  } finally { saving.value = false }
}

async function handleArchive(record) {
  try {
    await archive(record.id)
    message.success('已归档')
    loadData()
  } catch (e) {
    console.error('[项目] 归档失败：', e)
  }
}

function goDetail(record) {
  router.push({ path: '/crm/project/detail', query: { id: record.id } })
}

async function loadUsers() {
  try {
    const res = await listUsers({})
    storeUsers.value = (res?.data?.list?.data || res?.data?.list || []).filter(u => u.is_supplier !== 1)
  } catch (e) {
    console.error('[项目] 读取员工失败：', e)
  }
}

onMounted(() => { loadUsers(); loadData() })
</script>

<style scoped>
.project-page :deep(.ant-card-body) { padding: 20px 24px; }
.action-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
.action-right { display: flex; gap: 10px; align-items: center; }
.view-tabs { min-width: 360px; }
.view-tabs :deep(.ant-tabs-nav) { margin: 0; }
.form-hint { color: #999; font-size: 12px; line-height: 1.6; margin-top: 4px; }
</style>
