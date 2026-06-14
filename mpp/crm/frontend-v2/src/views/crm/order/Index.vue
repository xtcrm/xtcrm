<template>
  <a-card :bordered="false">
    <div class="table-page-search-wrapper">
      <a-form layout="inline">
        <a-row :gutter="24">
          <a-col :md="8">
            <a-form-item label="订单号">
              <a-input v-model:value="queryParam.order_no" allow-clear />
            </a-form-item>
          </a-col>
          <a-col :md="8">
            <a-form-item label="状态">
              <a-select v-model:value="queryParam.status" allow-clear placeholder="全部">
                <a-select-option value="">全部</a-select-option>
                <a-select-option :value="1">待确认</a-select-option>
                <a-select-option :value="2">生产中</a-select-option>
                <a-select-option :value="3">待发货</a-select-option>
                <a-select-option :value="4">已发货</a-select-option>
                <a-select-option :value="5">已完成</a-select-option>
                <a-select-option :value="6">已取消</a-select-option>
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
        <template v-if="column.key === 'order_no'">
          <a @click="handleDetail(record)">{{ record.order_no }}</a>
        </template>
        <template v-else-if="column.key === 'status'">
          <a-tag>{{ ['','待确认','生产中','待发货','已发货','已完成','已取消'][record.status] }}</a-tag>
        </template>
        <template v-else-if="column.key === 'final_amount'">
          {{ (record.final_amount || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}
        </template>
        <template v-else-if="column.key === 'action'">
          <a @click="handleDetail(record)">详情</a>
          <a-divider type="vertical" />
          <a-popconfirm title="确认删除?" @confirm="handleDelete(record)">
            <a style="color:#ff4d4f">删除</a>
          </a-popconfirm>
        </template>
      </template>
    </a-table>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { getList, deleteById } from '@/api/crm/order'

const router = useRouter()
const loading = ref(false)
const dataSource = ref([])
const queryParam = reactive({})
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const columns = [
  { title: '订单号', dataIndex: 'order_no', key: 'order_no', width: 180 },
  { title: '客户', dataIndex: 'customer_name', key: 'customer_name', width: 160 },
  { title: '币种', dataIndex: 'currency', key: 'currency', width: 60 },
  { title: '金额', dataIndex: 'final_amount', key: 'final_amount', width: 120, align: 'right' },
  { title: '未付', dataIndex: 'unpaid_amount', key: 'unpaid_amount', width: 120, align: 'right',
    customRender: ({ text }) => (text || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) },
  { title: '状态', dataIndex: 'status', key: 'status', width: 90 },
  { title: '付款', dataIndex: 'payment_status', key: 'payment_status', width: 80,
    customRender: ({ text }) => ['', '未付', '部分', '已付'][text] || '' },
  { title: '操作', key: 'action', width: 120 },
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
function handleDetail(r) { router.push({ path: '/crm/order/detail', query: { id: r.id } }) }
async function handleDelete(r) { await deleteById(r.id); message.success('删除成功'); loadData() }
function resetSearch() { Object.keys(queryParam).forEach(k => delete queryParam[k]); loadData(true) }

onMounted(() => { loadData() })
</script>
