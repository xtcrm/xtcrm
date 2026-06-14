<template>
  <a-card :bordered="false">
    <div class="table-operator">
      <a-button type="primary" @click="handleAdd"><plus-outlined /> 新增线索</a-button>
    </div>

    <div class="table-page-search-wrapper">
      <a-form layout="inline">
        <a-row :gutter="24">
          <a-col :md="8">
            <a-form-item label="线索名称">
              <a-input v-model:value="queryParam.lead_name" allow-clear />
            </a-form-item>
          </a-col>
          <a-col :md="8">
            <a-form-item label="状态">
              <a-select v-model:value="queryParam.status" allow-clear placeholder="全部">
                <a-select-option value="">全部</a-select-option>
                <a-select-option :value="1">新建</a-select-option>
                <a-select-option :value="2">跟进中</a-select-option>
                <a-select-option :value="3">已转客户</a-select-option>
                <a-select-option :value="4">已关闭</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :md="8">
            <a-button type="primary" @click="loadData(true)">查询</a-button>
            <a-button style="margin-left:8px" @click="resetSearch">重置</a-button>
          </a-col>
        </a-row>
      </a-form>
    </div>

    <a-table :columns="columns" :data-source="dataSource" :loading="loading" :pagination="pagination"
      :row-key="r => r.id" size="middle" @change="onTableChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'lead_name'">
          <a @click="handleDetail(record)">{{ record.lead_name }}</a>
        </template>
        <template v-else-if="column.key === 'priority'">
          <a-tag :color="record.priority === 3 ? 'red' : record.priority === 2 ? 'orange' : ''">{{ { 1: '低', 2: '中', 3: '高' }[record.priority] }}</a-tag>
        </template>
        <template v-else-if="column.key === 'status'">
          <a-tag :color="record.status === 3 ? 'green' : record.status === 4 ? 'default' : 'blue'">{{ { 1: '新建', 2: '跟进中', 3: '已转客户', 4: '已关闭' }[record.status] }}</a-tag>
        </template>
        <template v-else-if="column.key === 'action'">
          <a @click="handleDetail(record)">详情</a>
          <a-divider type="vertical" />
          <a v-if="record.status !== 3" @click="handleConvert(record)">转客户</a>
          <a-divider type="vertical" v-if="record.status !== 3" />
          <a @click="handleEdit(record)">编辑</a>
          <a-divider type="vertical" />
          <a-popconfirm title="删除?" @confirm="() => handleDelete(record)">
            <a style="color:#ff4d4f">删除</a>
          </a-popconfirm>
        </template>
      </template>
    </a-table>

    <LeadForm v-model:open="formVisible" :mdl="selected" @ok="onFormOk" />
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import { getList, getDetail, deleteById, convert } from '@/api/crm/lead'
import LeadForm from './components/LeadForm.vue'

const router = useRouter()
const loading = ref(false)
const dataSource = ref([])
const queryParam = reactive({})
const formVisible = ref(false)
const selected = ref(null)
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const columns = [
  { title: '线索名称', dataIndex: 'lead_name', key: 'lead_name', width: 180 },
  { title: '联系人', dataIndex: 'contact_person', key: 'contact_person', width: 100 },
  { title: '电话', dataIndex: 'contact_phone', key: 'contact_phone', width: 120 },
  { title: '公司', dataIndex: 'company_name', key: 'company_name', width: 150 },
  { title: '来源', dataIndex: 'source', key: 'source', width: 80 },
  { title: '优先级', dataIndex: 'priority', key: 'priority', width: 70 },
  { title: '预计金额', dataIndex: 'expected_amount', key: 'expected_amount', width: 100, align: 'right' },
  { title: '状态', dataIndex: 'status', key: 'status', width: 90 },
  { title: '操作', key: 'action', width: 210 },
]

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { page: pagination.current, limit: pagination.pageSize, ...queryParam }
    const res = await getList(params)
    if (res?.status === 200) {
      const list = res.data?.list
      dataSource.value = list?.data || []
      pagination.total = list?.total || 0
    }
  } finally { loading.value = false }
}

function onTableChange(pag) { pagination.current = pag.current; pagination.pageSize = pag.pageSize; loadData() }

function handleAdd() { selected.value = null; formVisible.value = true }
function handleEdit(r) {
  getDetail(r.id).then(res => { if (res?.status === 200) { selected.value = res.data; formVisible.value = true } })
}
function handleDetail(r) { router.push({ path: '/crm/lead/detail', query: { id: r.id } }) }
async function handleDelete(r) { await deleteById(r.id); message.success('已删除'); loadData() }
async function handleConvert(r) {
  const res = await convert(r.id)
  if (res?.status === 200) { message.success('已转为客户: ' + res.data.customer_name); loadData() }
}
function onFormOk() { formVisible.value = false; loadData(true) }
function resetSearch() { Object.keys(queryParam).forEach(k => delete queryParam[k]); loadData(true) }

onMounted(() => { loadData() })
</script>
