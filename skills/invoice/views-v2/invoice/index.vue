<template>
  <a-card :bordered="false">
    <div class="table-page-search-wrapper">
      <a-row :gutter="12">
        <a-col :md="4">
          <a-select v-model:value="queryParam.status" placeholder="状态筛选" allow-clear @change="refresh" style="width:100%">
            <a-select-option value="">全部状态</a-select-option>
            <a-select-option :value="0">待审核</a-select-option>
            <a-select-option :value="1">待开票</a-select-option>
            <a-select-option :value="2">已开票</a-select-option>
            <a-select-option :value="3">已作废</a-select-option>
            <a-select-option :value="4">已驳回</a-select-option>
          </a-select>
        </a-col>
        <a-col :md="4">
          <a-input v-model:value="queryParam.invoice_number" placeholder="发票号码" allow-clear />
        </a-col>
        <a-col :md="4">
          <a-input v-model:value="queryParam.company_id" placeholder="公司ID" allow-clear />
        </a-col>
        <a-col :md="4">
          <a-button type="primary" @click="refresh">查询</a-button>
          <a-button style="margin-left:8px" @click="resetQuery();refresh()">重置</a-button>
        </a-col>
      </a-row>
    </div>
    <a-table :columns="columns" :data-source="list" :loading="loading" :pagination="pagination" :row-key="r => r.id" size="small" @change="handleTableChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.dataIndex === 'status'">
          <a-tag :color="statusColor[record.status]">{{ statusText[record.status] }}</a-tag>
        </template>
        <template v-else-if="column.dataIndex === 'action'">
          <a @click="handleDetail(record)">详情</a>
          <a-divider type="vertical" />
          <a v-if="record.status === 2" @click="handleNotify(record)">通知</a>
          <a-divider v-if="record.status === 2" type="vertical" />
          <a-popconfirm title="确认删除?" @confirm="handleDelete(record)"><a class="danger">删除</a></a-popconfirm>
        </template>
      </template>
    </a-table>
    <audit-modal v-model:visible="auditVisible" :record="selectedRecord" @ok="onSaved" />
    <complete-modal v-model:visible="completeVisible" :record="selectedRecord" @ok="onSaved" />
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { getInvoiceList, deleteById, notifyInvoice } from '../api'
import AuditModal from './components/AuditModal.vue'
import CompleteModal from './components/CompleteModal.vue'

const router = useRouter()
const queryParam = reactive({})
const selectedRecord = ref(null)
const auditVisible = ref(false)
const completeVisible = ref(false)
const list = ref([])
const loading = ref(false)
const pagination = reactive({ current: 1, pageSize: 20, total: 0 })

const statusText = { 0: '待审核', 1: '待开票', 2: '已开票', 3: '已作废', 4: '已驳回' }
const statusColor = { 0: 'orange', 1: 'blue', 2: 'green', 3: 'default', 4: 'red' }

const columns = [
  { title: '发票号码', dataIndex: 'invoice_number', width: 140 },
  { title: '销方', dataIndex: 'seller_name', width: 140 },
  { title: '购方', dataIndex: 'buyer_name' },
  { title: '金额', dataIndex: 'total_amount', width: 100 },
  { title: '状态', dataIndex: 'status', width: 80 },
  { title: '申请时间', dataIndex: 'create_time', width: 110 },
  { title: '操作', dataIndex: 'action', width: 150 },
]

function loadData() {
  loading.value = true
  const params = { page: pagination.current, pageSize: pagination.pageSize, ...queryParam }
  getInvoiceList(params).then(r => {
    const res = r?.data?.list || r?.data || {}
    list.value = res.data || res.list || []
    pagination.total = res.total || 0
  }).finally(() => { loading.value = false })
}

function handleTableChange(p) { pagination.current = p.current; loadData() }
function resetQuery() { Object.keys(queryParam).forEach(k => delete queryParam[k]) }
function refresh() { pagination.current = 1; loadData() }
function handleDetail(record) {
  if (!record?.id) return
  router.push({ name: 'invoice-detail', query: { id: record.id } })
}
function handleNotify(record) {
  notifyInvoice(record.id).then(() => { message.success('短信已发送'); refresh() })
}
function handleDelete(record) {
  deleteById(record.id).then(() => { message.success('删除成功'); refresh() })
}
function onSaved() { auditVisible.value = false; completeVisible.value = false; refresh() }

onMounted(loadData)
</script>
