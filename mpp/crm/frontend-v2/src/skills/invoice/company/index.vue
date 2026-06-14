<template>
  <a-card :bordered="false">
    <div class="table-operator">
      <a-button type="primary" @click="handleAdd">后台建档</a-button>
    </div>
    <div class="table-page-search-wrapper">
      <a-row :gutter="12">
        <a-col :md="5">
          <a-input v-model:value="queryParam.name" placeholder="公司名称" allow-clear />
        </a-col>
        <a-col :md="4">
          <a-select v-model:value="queryParam.status" placeholder="状态筛选" allow-clear @change="refresh" style="width:100%">
            <a-select-option value="">全部状态</a-select-option>
            <a-select-option :value="1">正常</a-select-option>
            <a-select-option :value="0">已禁用</a-select-option>
          </a-select>
        </a-col>
        <a-col :md="4">
          <a-input v-model:value="queryParam.tax_number" placeholder="税号" allow-clear />
        </a-col>
        <a-col :md="3">
          <a-button type="primary" @click="refresh">查询</a-button>
          <a-button style="margin-left:8px" @click="resetQuery();refresh()">重置</a-button>
        </a-col>
      </a-row>
    </div>
    <a-table :columns="columns" :data-source="list" :loading="loading" :pagination="pagination" :row-key="r => r.id" size="small" @change="handleTableChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.dataIndex === 'status'">
          <a-tag :color="record.status === 1 ? 'green' : 'default'">{{ record.status === 1 ? '正常' : '已禁用' }}</a-tag>
        </template>
        <template v-else-if="column.dataIndex === 'source'">
          <a-tag>{{ record.source === 1 ? '小程序' : '后台' }}</a-tag>
        </template>
        <template v-else-if="column.dataIndex === 'action'">
          <a @click="handleDetail(record)">详情</a>
          <a-divider type="vertical" />
          <a @click="handleEdit(record)">编辑</a>
          <a-divider type="vertical" />
          <a @click="handleToggle(record)">{{ record.status === 1 ? '禁用' : '启用' }}</a>
        </template>
      </template>
    </a-table>

    <a-modal :title="editingId ? '编辑公司' : '后台建档'" :visible="formVisible" :confirm-loading="saving" @ok="handleSave" @cancel="formVisible = false">
      <a-form :model="form">
        <a-form-item label="公司名称" required><a-input v-model:value="form.name" /></a-form-item>
        <a-form-item label="税号" required><a-input v-model:value="form.tax_number" /></a-form-item>
        <a-form-item label="地址"><a-input v-model:value="form.address" /></a-form-item>
        <a-form-item label="电话"><a-input v-model:value="form.phone" /></a-form-item>
        <a-form-item label="开户银行"><a-input v-model:value="form.bank_name" /></a-form-item>
        <a-form-item label="银行账号"><a-input v-model:value="form.bank_account" /></a-form-item>
      </a-form>
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { getCompanyList, addCompany, editCompany, toggleCompanyStatus } from '../api'

const router = useRouter()
const queryParam = reactive({})
const formVisible = ref(false)
const saving = ref(false)
const editingId = ref(0)
const form = reactive({ name: '', tax_number: '', address: '', phone: '', bank_name: '', bank_account: '' })
const list = ref([])
const loading = ref(false)
const pagination = reactive({ current: 1, pageSize: 20, total: 0 })

const columns = [
  { title: '公司名称', dataIndex: 'name', width: 200 },
  { title: '税号', dataIndex: 'tax_number', width: 150 },
  { title: '状态', dataIndex: 'status', width: 80 },
  { title: '来源', dataIndex: 'source', width: 80 },
  { title: '建档时间', dataIndex: 'create_time', width: 110 },
  { title: '操作', dataIndex: 'action', width: 170 },
]

function loadData() {
  loading.value = true
  const params = { page: pagination.current, pageSize: pagination.pageSize }
  // 过滤空值
  Object.keys(queryParam).forEach(k => { if (queryParam[k] !== '' && queryParam[k] !== null) params[k] = queryParam[k] })
  getCompanyList(params).then(r => {
    const res = r?.data?.list || r?.data || {}
    list.value = res.data || res.list || []
    pagination.total = res.total || 0
  }).finally(() => { loading.value = false })
}

function handleTableChange(p) { pagination.current = p.current; loadData() }
function resetQuery() { Object.keys(queryParam).forEach(k => delete queryParam[k]) }
function refresh() { pagination.current = 1; loadData() }
function handleAdd() { editingId.value = 0; resetForm(); formVisible.value = true }
function handleEdit(record) {
  editingId.value = record.id
  Object.assign(form, {
    name: record.name, tax_number: record.tax_number,
    address: record.address || '', phone: record.phone || '',
    bank_name: record.bank_name || '', bank_account: record.bank_account || ''
  })
  formVisible.value = true
}
function resetForm() { Object.keys(form).forEach(k => form[k] = '') }
function handleDetail(record) { router.push({ name: 'invoice-company-detail', query: { id: record.id } }) }
function handleToggle(record) { toggleCompanyStatus(record.id).then(() => { message.success('操作成功'); refresh() }) }
function handleSave() {
  if (!form.name || !form.tax_number) { message.warning('公司名称和税号必填'); return }
  saving.value = true
  const api = editingId.value ? editCompany({ id: editingId.value, ...form }) : addCompany({ ...form })
  api.then(() => { message.success('保存成功'); formVisible.value = false; refresh() }).finally(() => { saving.value = false })
}

onMounted(loadData)
</script>
