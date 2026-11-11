<template>
  <a-card :bordered="false" class="catalog-page">
    <div class="action-bar">
      <div class="hint">
        这里维护你们对外提供的服务项目。需求、报价、工单都会引用；停用后不再出现在新建表单，历史数据不受影响。
      </div>
      <a-button type="primary" @click="openCreate"><template #icon><plus-outlined /></template>新增服务品类</a-button>
    </div>

    <a-table :columns="columns" :data-source="dataSource" :loading="loading" :pagination="false" row-key="id" size="middle" bordered>
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'name'">
          <strong>{{ record.name }}</strong>
          <span v-if="record.remark" class="row-remark">{{ record.remark }}</span>
        </template>
        <template v-else-if="column.key === 'price'">
          {{ Number(record.price || 0) > 0 ? money(record.price) : '面议' }}
        </template>
        <template v-else-if="column.key === 'billing_cycle'">
          {{ billingText(record.billing_cycle) }}
        </template>
        <template v-else-if="column.key === 'default_delivery_days'">
          {{ Number(record.default_delivery_days) > 0 ? record.default_delivery_days + ' 天' : '不限' }}
        </template>
        <template v-else-if="column.key === 'assignee'">
          {{ userName(record.default_assignee_user_id) || '—' }}
        </template>
        <template v-else-if="column.key === 'status'">
          <a-switch :checked="record.status === 1" checked-children="启用" un-checked-children="停用"
            size="small" @change="(v) => toggleStatus(record, v)" />
        </template>
        <template v-else-if="column.key === 'action'">
          <a @click="openEdit(record)">编辑</a>
          <a-divider type="vertical" />
          <a-popconfirm title="确认删除？被工单或材料模板引用的品类不能删，只能停用" @confirm="handleDelete(record)">
            <a style="color:#ff4d4f">删除</a>
          </a-popconfirm>
        </template>
      </template>
    </a-table>

    <a-modal v-model:open="formVisible" :title="editingId ? '编辑服务品类' : '新增服务品类'"
      :confirm-loading="saving" width="620px" @ok="submitForm">
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="品类名称" required>
          <a-input v-model:value="form.name" placeholder="例如：注册公司 / 代理记账 / 商标注册" />
        </a-form-item>
        <a-form-item label="标准价">
          <a-input-number v-model:value="form.price" :min="0" :precision="2" style="width:180px" />
          <span class="form-hint-inline">0 表示面议</span>
        </a-form-item>
        <a-form-item label="计费周期">
          <a-select v-model:value="form.billing_cycle" style="width:180px">
            <a-select-option :value="1">一次性</a-select-option>
            <a-select-option :value="2">按月</a-select-option>
            <a-select-option :value="3">按季</a-select-option>
            <a-select-option :value="4">按年</a-select-option>
            <a-select-option :value="5">按次</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="默认交付天数">
          <a-input-number v-model:value="form.default_delivery_days" :min="0" style="width:180px" />
          <span class="form-hint-inline">0 表示不限</span>
        </a-form-item>
        <a-form-item label="默认交付人">
          <a-select v-model:value="form.default_assignee_user_id" allow-clear show-search :options="userOptions" style="width:100%" />
        </a-form-item>
        <a-form-item label="排序">
          <a-input-number v-model:value="form.sort" :min="0" :max="999" style="width:120px" />
        </a-form-item>
        <a-form-item label="说明">
          <a-textarea v-model:value="form.remark" :rows="2" placeholder="选填，例如：含开户、含首年记账" />
        </a-form-item>
      </a-form>
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import { getList, add, edit, deleteById, setStatus } from '@/api/crm/catalog'
import { list as listUsers } from '@/api/user'

const loading = ref(false)
const saving = ref(false)
const dataSource = ref([])

const storeUsers = ref([])
const userOptions = computed(() => storeUsers.value.map(u => ({
  value: u.store_user_id,
  label: u.real_name || u.user_name || ('#' + u.store_user_id),
})))

const columns = [
  { title: '品类名称', dataIndex: 'name', key: 'name', width: 220 },
  { title: '标准价', key: 'price', width: 120, align: 'right' },
  { title: '计费周期', key: 'billing_cycle', width: 100 },
  { title: '默认交付', key: 'default_delivery_days', width: 100 },
  { title: '默认交付人', key: 'assignee', width: 120 },
  { title: '排序', dataIndex: 'sort', key: 'sort', width: 70, align: 'center' },
  { title: '状态', key: 'status', width: 90, align: 'center' },
  { title: '操作', key: 'action', width: 120 },
]

const formVisible = ref(false)
const editingId = ref(0)
const form = reactive({
  name: '',
  price: 0,
  billing_cycle: 1,
  default_delivery_days: 0,
  default_assignee_user_id: undefined,
  sort: 100,
  remark: '',
})

function money(v) { return Number(v || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }
function billingText(v) { return { 1: '一次性', 2: '按月', 3: '按季', 4: '按年', 5: '按次' }[v] || '一次性' }
function userName(id) { return userOptions.value.find(u => u.value === id)?.label || '' }

async function loadData() {
  loading.value = true
  try {
    const res = await getList()
    dataSource.value = Array.isArray(res?.data) ? res.data : (res?.data?.list || [])
  } catch (e) {
    console.error('[服务目录] 加载失败：', e)
  } finally { loading.value = false }
}

function resetForm() {
  form.name = ''
  form.price = 0
  form.billing_cycle = 1
  form.default_delivery_days = 0
  form.default_assignee_user_id = undefined
  form.sort = 100
  form.remark = ''
}

function openCreate() {
  editingId.value = 0
  resetForm()
  formVisible.value = true
}

function openEdit(record) {
  editingId.value = record.id
  form.name = record.name
  form.price = Number(record.price) || 0
  form.billing_cycle = Number(record.billing_cycle) || 1
  form.default_delivery_days = Number(record.default_delivery_days) || 0
  form.default_assignee_user_id = record.default_assignee_user_id || undefined
  form.sort = Number(record.sort) || 100
  form.remark = record.remark || ''
  formVisible.value = true
}

async function submitForm() {
  if (!form.name.trim()) { message.warning('请输入品类名称'); return }
  saving.value = true
  try {
    const payload = {
      name: form.name.trim(),
      price: form.price || 0,
      billing_cycle: form.billing_cycle || 1,
      default_delivery_days: form.default_delivery_days || 0,
      default_assignee_user_id: form.default_assignee_user_id || 0,
      sort: form.sort ?? 100,
      remark: form.remark || '',
    }
    if (editingId.value) {
      await edit({ id: editingId.value, ...payload })
      message.success('保存成功')
    } else {
      await add(payload)
      message.success('已添加')
    }
    formVisible.value = false
    loadData()
  } catch (e) {
    console.error('[服务目录] 保存失败：', e)
  } finally { saving.value = false }
}

async function toggleStatus(record, checked) {
  try {
    await setStatus(record.id, checked ? 1 : 0)
    record.status = checked ? 1 : 0
    message.success(checked ? '已启用' : '已停用')
  } catch (e) {
    console.error('[服务目录] 状态更新失败：', e)
  }
}

async function handleDelete(record) {
  try {
    await deleteById(record.id)
    message.success('已删除')
    loadData()
  } catch (e) {
    console.error('[服务目录] 删除失败：', e)
  }
}

async function loadUsers() {
  try {
    const res = await listUsers({})
    storeUsers.value = (res?.data?.list?.data || res?.data?.list || []).filter(u => u.is_supplier !== 1)
  } catch (e) { console.error('[服务目录] 读取员工失败：', e) }
}

onMounted(() => { loadUsers(); loadData() })
</script>

<style scoped>
.catalog-page :deep(.ant-card-body) { padding: 20px 24px; }
.action-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; }
.hint { color: #999; font-size: 12px; }
.row-remark { color: #999; font-size: 12px; margin-left: 8px; }
.form-hint-inline { color: #999; font-size: 12px; margin-left: 8px; }
</style>
