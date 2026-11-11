<template>
  <a-card :bordered="false">
    <a-page-header :title="'订单 ' + detail.order_no" @back="() => router.go(-1)">
      <template #tags>
        <a-tag :color="statusColor">{{ detail.status_text }}</a-tag>
        <a-tag :color="payColor">{{ detail.payment_status_text }}</a-tag>
      </template>
      <template #extra>
        <a-select v-model:value="newStatus" style="width:100px" @change="doChangeStatus">
          <a-select-option :value="1">待确认</a-select-option>
          <a-select-option :value="2">生产中</a-select-option>
          <a-select-option :value="3">待发货</a-select-option>
          <a-select-option :value="4">已发货</a-select-option>
          <a-select-option :value="5">已完成</a-select-option>
          <a-select-option :value="6">已取消</a-select-option>
        </a-select>
        <a-button @click="formVisible = true" style="margin-left:8px">编辑</a-button>
        <a-button v-if="detail.status >= 2" type="primary" ghost @click="showContractForm = true" style="margin-left:8px">转合同</a-button>
        <a-popconfirm title="确认删除?" @confirm="handleDelete">
          <a-button danger style="margin-left:8px">删除</a-button>
        </a-popconfirm>
      </template>
    </a-page-header>

    <a-descriptions bordered size="small" :column="3">
      <a-descriptions-item label="客户">{{ detail.customer_name }}</a-descriptions-item>
      <a-descriptions-item label="订单日期">{{ fmtDate(detail.order_date) }}</a-descriptions-item>
      <a-descriptions-item label="预计交付">{{ fmtDate(detail.delivery_date) }}</a-descriptions-item>
      <a-descriptions-item label="币种">{{ detail.currency }}</a-descriptions-item>
      <a-descriptions-item label="订单金额">{{ fmtAmount(detail.final_amount) }}</a-descriptions-item>
      <a-descriptions-item label="已付金额">{{ fmtAmount(detail.paid_amount) }}</a-descriptions-item>
      <a-descriptions-item label="未付金额"><span :style="{ color: (detail.unpaid_amount || 0) > 0 ? '#ff4d4f' : '#52c41a', fontWeight: 600 }">{{ fmtAmount(detail.unpaid_amount) }}</span></a-descriptions-item>
      <a-descriptions-item label="负责人">{{ detail.owner_user_name }}</a-descriptions-item>
      <a-descriptions-item label="备注">{{ detail.remark || '-' }}</a-descriptions-item>
    </a-descriptions>

    <a-divider />
    <h4>订单明细</h4>
    <a-table :data-source="detail.items || []" :pagination="false" bordered size="small" row-key="id">
      <a-table-column title="#" key="idx" width="40" align="center">
        <template #default="{ index }">{{ index + 1 }}</template>
      </a-table-column>
      <a-table-column title="产品" data-index="product_name" key="product_name" />
      <a-table-column title="规格" data-index="specification" key="specification" />
      <a-table-column title="单位" data-index="unit" key="unit" width="60" />
      <a-table-column title="数量" data-index="quantity" key="quantity" width="80" align="right" />
      <a-table-column title="单价" data-index="unit_price" key="unit_price" width="100" align="right">
        <template #default="{ text }">{{ fmtAmount(text) }}</template>
      </a-table-column>
      <a-table-column title="小计" data-index="amount" key="amount" width="100" align="right">
        <template #default="{ text }">{{ fmtAmount(text) }}</template>
      </a-table-column>
    </a-table>

    <OrderForm v-model:open="formVisible" :mdl="detail" @ok="loadDetail" />
    <ContractForm v-model:open="showContractForm" :order="detail" @ok="onContractCreated" />
  </a-card>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import dayjs from 'dayjs'
import { getDetail, deleteById, changeStatus } from '@/api/crm/order'
import OrderForm from './components/OrderForm.vue'
import ContractForm from '@/views/crm/contract/components/ContractForm.vue'
import { useTabsStore, tabKeyOf } from '@/stores/tabs'

const route = useRoute()
const router = useRouter()
const tabsStore = useTabsStore()
const detail = reactive({})
const formVisible = ref(false)
const showContractForm = ref(false)
const newStatus = ref(null)

const statusColor = computed(() => {
  const m = { 1: 'default', 2: 'blue', 3: 'orange', 4: 'cyan', 5: 'green', 6: 'red' }
  return m[detail.status] || 'default'
})
const payColor = computed(() => {
  return detail.payment_status === 3 ? 'green' : detail.payment_status === 2 ? 'orange' : 'default'
})

function fmtDate(ts) { return ts ? dayjs(ts * 1000).format('YYYY-MM-DD') : '-' }
function fmtAmount(v) { return (v || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }

function loadDetail() {
  const id = route.query.id
  if (!id) return
  // 捕获页签 key：本页被 keep-alive 缓存，异步响应回来时 route 可能已变
  const tabKey = tabKeyOf(route)
  getDetail(id).then(r => {
    Object.assign(detail, r?.data?.detail || r?.data || {})
    newStatus.value = detail.status
    tabsStore.setTitle(tabKey, detail.order_no)
  }).catch(() => {})
}

function doChangeStatus(s) {
  changeStatus(detail.id, s).then(() => { message.success('状态已更新'); loadDetail() })
}
function onContractCreated() { showContractForm.value = false; message.success('已转为合同') }
function handleDelete() {
  deleteById(detail.id).then(() => { message.success('已删除'); router.go(-1) })
}

onMounted(() => { loadDetail() })
</script>
