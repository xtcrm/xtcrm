<template>
  <a-card :bordered="false">
    <div class="table-operator">
      <a-button type="primary" @click="handleAdd"><plus-outlined /> 新增报价单</a-button>
    </div>
    <div class="table-page-search-wrapper">
      <a-form layout="inline">
        <a-row :gutter="24">
          <a-col :md="6" :sm="24">
            <a-form-item label="客户名称"><a-input v-model:value="queryParam.customer_name" placeholder="客户名称" allow-clear /></a-form-item>
          </a-col>
          <a-col :md="6" :sm="24">
            <a-form-item label="状态">
              <a-select v-model:value="queryParam.status" placeholder="全部" allow-clear>
                <a-select-option value="">全部</a-select-option>
                <a-select-option :value="1">草稿</a-select-option><a-select-option :value="2">已发送</a-select-option>
                <a-select-option :value="3">已确认</a-select-option><a-select-option :value="4">已拒绝</a-select-option>
                <a-select-option :value="5">已转订单</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :md="12" :sm="24">
            <a-form-item label="报价日期"><a-range-picker v-model:value="queryParam.dateRange" style="width:100%" /></a-form-item>
          </a-col>
          <a-col :md="24" :sm="24">
            <a-button type="primary" @click="loadData(true)">查询</a-button>
            <a-button style="margin-left:8px" @click="resetSearch">重置</a-button>
          </a-col>
        </a-row>
      </a-form>
    </div>

    <a-table :columns="columns" :data-source="dataSource" :loading="loading" :pagination="pagination"
      :row-key="r => r.id" size="middle" @change="onTableChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'quotation_no'"><a @click="handleDetail(record)">{{ record.quotation_no }}</a></template>
        <template v-else-if="column.key === 'status'">
          <a-tag v-if="record.status===1">草稿</a-tag>
          <a-tag v-else-if="record.status===2" color="blue">已发送</a-tag>
          <a-tag v-else-if="record.status===3" color="green">已确认</a-tag>
          <a-tag v-else-if="record.status===4" color="red">已拒绝</a-tag>
          <a-tag v-else-if="record.status===5" color="purple">已转订单</a-tag>
        </template>
        <template v-else-if="column.key === 'final_amount'">{{ (record.final_amount||0).toLocaleString('zh-CN',{minimumFractionDigits:2}) }}</template>
        <template v-else-if="column.key === 'quotation_date'">{{ record.quotation_date ? new Date(record.quotation_date*1000).toLocaleDateString() : '' }}</template>
        <template v-else-if="column.key === 'action'">
          <a @click="handleDetail(record)">详情</a><a-divider type="vertical" />
          <a v-if="record.status===1" @click="handleSend(record)">发送</a><a-divider type="vertical" v-if="record.status===1" />
          <a @click="handleEdit(record)">编辑</a><a-divider type="vertical" />
          <a-popconfirm title="确认删除?" @confirm="handleDelete(record)"><a style="color:#ff4d4f">删除</a></a-popconfirm>
        </template>
      </template>
    </a-table>
    <QuotationForm v-model:open="formVisible" :mdl="selected" @ok="onFormOk" />
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import dayjs from 'dayjs'
import { getList, getDetail, deleteById, changeStatus } from '@/api/crm/quotation'
import QuotationForm from './components/QuotationForm.vue'

const router = useRouter()
const loading = ref(false)
const dataSource = ref([])
const queryParam = reactive({})
const formVisible = ref(false)
const selected = ref(null)
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const columns = [
  { title: '报价单号', dataIndex: 'quotation_no', key: 'quotation_no', width: 180 },
  { title: '客户名称', dataIndex: 'customer_name', key: 'customer_name', width: 180 },
  { title: '报价日期', dataIndex: 'quotation_date', key: 'quotation_date', width: 110 },
  { title: '币种', dataIndex: 'currency', key: 'currency', width: 60 },
  { title: '折后金额', dataIndex: 'final_amount', key: 'final_amount', width: 120, align: 'right' },
  { title: '状态', dataIndex: 'status', key: 'status', width: 90 },
  { title: '操作', key: 'action', width: 210 },
]

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { page: pagination.current, limit: pagination.pageSize, ...queryParam }
    if (params.dateRange && params.dateRange.length === 2) {
      params.quotation_date_from = dayjs(params.dateRange[0]).format('YYYY-MM-DD')
      params.quotation_date_to = dayjs(params.dateRange[1]).format('YYYY-MM-DD')
    }
    delete params.dateRange
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
function handleEdit(record) { getDetail(record.id).then(res => { if (res?.status === 200) { selected.value = res.data?.detail || res.data; formVisible.value = true } }) }
function handleDetail(record) { router.push({ path: '/crm/quotation/detail', query: { id: record.id } }) }
async function handleDelete(record) { await deleteById(record.id); message.success('删除成功'); loadData() }
async function handleSend(record) { await changeStatus(record.id, 2); message.success('已发送'); loadData() }
function onFormOk() { formVisible.value = false; loadData() }
function resetSearch() { Object.keys(queryParam).forEach(k => delete queryParam[k]); loadData(true) }

onMounted(() => { loadData() })
</script>
