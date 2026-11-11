<template>
  <a-card :bordered="false" class="quotation-page">
    <!-- 操作栏 -->
    <div class="action-bar">
      <div class="action-left">
        <a-input v-model:value="queryParam.customer_name" placeholder="搜索客户名称..." allow-clear style="width:200px" @pressEnter="loadData(true)" />
        <a-select v-model:value="queryParam.status" placeholder="状态" allow-clear style="width:110px" @change="loadData(true)">
          <a-select-option value="">全部</a-select-option>
          <a-select-option :value="1">草稿</a-select-option><a-select-option :value="2">已发送</a-select-option>
          <a-select-option :value="3">已确认</a-select-option><a-select-option :value="4">已拒绝</a-select-option>
          <a-select-option :value="5">已转订单</a-select-option>
        </a-select>
        <a-range-picker v-model:value="queryParam.dateRange" style="width:230px" @change="loadData(true)" />
      </div>
      <div class="action-right">
        <a-button type="primary" @click="handleAdd"><template #icon><plus-outlined /></template>新增报价单</a-button>
      </div>
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
    <QuotationForm v-model:open="formVisible" :mdl="selected" :preset="preset" @ok="onFormOk" />
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted, onActivated } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import dayjs from 'dayjs'
import { getList, getDetail, deleteById, changeStatus } from '@/api/crm/quotation'
import QuotationForm from './components/QuotationForm.vue'
import { getDetail as getIntentDetail } from '@/api/crm/intent'
import { getList as getCatalog } from '@/api/crm/catalog'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const dataSource = ref([])
const queryParam = reactive({})
const formVisible = ref(false)
const selected = ref(null)
const preset = ref(null)
let handledIntentId = 0
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
function handleAdd() { selected.value = null; preset.value = null; formVisible.value = true }
function handleEdit(record) { preset.value = null; getDetail(record.id).then(res => { if (res?.status === 200) { selected.value = res.data?.detail || res.data; formVisible.value = true } }) }
function handleDetail(record) { router.push({ path: '/crm/quotation/detail', query: { id: record.id } }) }
async function handleDelete(record) { await deleteById(record.id); message.success('删除成功'); loadData() }
async function handleSend(record) { await changeStatus(record.id, 2); message.success('已发送'); loadData() }
function onFormOk() { formVisible.value = false; loadData() }
function resetSearch() { Object.keys(queryParam).forEach(k => delete queryParam[k]); loadData(true) }

onMounted(() => { loadData(); maybeOpenFromIntent() })
onActivated(() => { maybeOpenFromIntent() })

/** 从需求池「报价」跳进来：带出客户、项目、服务与标准价 */
async function maybeOpenFromIntent() {
  const intentId = parseInt(route.query.intent_id)
  if (!intentId || intentId === handledIntentId) return
  handledIntentId = intentId
  try {
    const [intentRes, catalogRes] = await Promise.all([getIntentDetail(intentId), getCatalog()])
    const intent = intentRes?.data?.intent
    if (!intent) return
    const catalogs = Array.isArray(catalogRes?.data) ? catalogRes.data : (catalogRes?.data?.list || [])
    const cat = catalogs.find(c => Number(c.id) === Number(intent.catalog_id))
    preset.value = {
      customer_id: intent.customer_id,
      project_id: intent.project_id || 0,
      items: [{
        intent_id: intent.id,
        catalog_id: intent.catalog_id || 0,
        product_name: intent.service_name || intent.title,
        unit: intent.unit || '',
        quantity: Number(intent.quantity) || 1,
        unit_price: Number(cat?.price) || 0,
      }],
    }
    selected.value = null
    formVisible.value = true
  } catch (e) {
    console.error('[报价] 读取需求失败：', e)
  }
}
</script>
<style scoped>
.action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding: 12px 16px; background: #fff; border-radius: 6px; border: 1px solid #f0f0f0; }
.action-left { display: flex; gap: 10px; align-items: center; }
.action-right { display: flex; gap: 10px; align-items: center; flex-shrink: 0; }
.quotation-page :deep(.ant-card-body) { padding: 24px; }
</style>
